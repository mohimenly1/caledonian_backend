<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\StudentTermSubjectGrade;
use App\Models\StudentFinalYearGrade;
use App\Models\GradingScale;
use App\Models\CourseOffering;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\GradingPolicy;

class FinalGradeCalculationController extends Controller
{
    /**
     * حساب وتخزين الدرجات السنوية النهائية لصف معين.
     * POST /api/final-grade-calculation/run
     */
    public function calculateFinalYearGrades(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $class = ClassRoom::with('studyYear')->findOrFail($validated['class_id']);
        $studyYear = $class->studyYear;

        $students = Student::where('class_id', $class->id)
                           ->where('study_year_id', $studyYear->id)
                           ->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students found for the selected class.'], 404);
        }

        $gradingScale = GradingScale::where('study_year_id', $studyYear->id)->get();
        if ($gradingScale->isEmpty()) {
            return response()->json(['message' => 'No grading scale found for the study year. Please define one first.'], 422);
        }

        DB::transaction(function () use ($students, $studyYear, $class, $gradingScale) {
            foreach ($students as $student) {
                // تجميع الدرجات الفصلية للطالب وتجميعها حسب المادة
                $termGradesBySubject = StudentTermSubjectGrade::where('student_id', $student->id)
                    ->whereHas('courseOffering', function ($q) use ($studyYear) {
                        $q->where('study_year_id', $studyYear->id);
                    })
                    ->with('courseOffering.subject')
                    ->get()
                    ->groupBy('courseOffering.subject_id');

                foreach ($termGradesBySubject as $subjectId => $termGrades) {
                    // حساب المعدل السنوي للمادة
                    $yearlyAverage = $termGrades->avg('weighted_average_score_percentage');
                    
                    // تحديد التقدير النهائي
                    $finalGrade = $this->getGradeFromScale($yearlyAverage, $gradingScale);

                    StudentFinalYearGrade::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'subject_id' => $subjectId,
                            'study_year_id' => $studyYear->id,
                            'class_id' => $class->id,
                        ],
                        [
                            'overall_numerical_score_percentage' => round($yearlyAverage, 2),
                            'final_grading_scale_id' => $finalGrade ? $finalGrade->id : null,
                            'promotion_status' => ($finalGrade && $finalGrade->min_percentage >= 50) ? 'Promoted' : 'Retained', // منطق ترفيع مبدئي
                            'is_finalized' => true,
                            'finalized_by_user_id' => Auth::id(),
                            'finalized_timestamp' => now(),
                        ]
                    );
                }
            }
        });

        return response()->json(['message' => 'Final year grades have been calculated and stored successfully.']);
    }

    /**
     * الحصول على التقدير المقابل من مقياس التقدير.
     */
    private function getGradeFromScale(float $percentage, Collection $scale)
    {
        return $scale->first(function ($grade) use ($percentage) {
            return bccomp((string)$percentage, (string)$grade->min_percentage, 2) >= 0 && bccomp((string)$percentage, (string)$grade->max_percentage, 2) <= 0;
        });
    }

        /**
     * جلب قائمة الصفوف لسنة دراسية محددة مع عدد الطلاب.
     * GET /api/final-grade-calculation/classes
     */
    public function getClassesForYear(Request $request)
    {
        $validated = $request->validate([
            'study_year_id' => 'required|exists:study_years,id',
        ]);

        $classes = ClassRoom::where('study_year_id', $validated['study_year_id'])
                        ->with('gradeLevel:id,name')
                        ->withCount('students')
                        ->orderBy('name')
                        ->get();

        // --- التعديل الرئيسي هنا: إضافة حالة كل صف ---
        $classes->each(function ($class) {
            // تحقق مما إذا كان هناك أي سجل درجة نهائية معتمد لهذا الصف
            $class->is_finalized = StudentFinalYearGrade::where('class_id', $class->id)
                                    ->where('study_year_id', $class->study_year_id)
                                    ->where('is_finalized', true)
                                    ->exists();
        });

        return response()->json($classes);
    }



       /**
     * جلب البيانات المفصلة لعرضها في "صحيفة النجاح والرسوب النهائية".
     * GET /api/final-grade-calculation/final-record
     */
    public function getFinalRecordData(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $class = ClassRoom::with('studyYear')->findOrFail($validated['class_id']);
        $studyYear = $class->studyYear;
        
        // 1. جلب جميع المواد التي يدرسها هذا الصف في هذه السنة
        $subjects = $class->courseOfferings()
                          ->where('study_year_id', $studyYear->id)
                          ->with('subject:id,name')
                          ->get()
                          ->pluck('subject')
                          ->unique('id')
                          ->sortBy('name');

        // 2. جلب جميع طلاب الصف
        $students = Student::where('class_id', $class->id)
                           ->where('study_year_id', $studyYear->id)
                           ->orderBy('name')
                           ->get();
        
        // 3. تجميع بيانات كل طالب
        $studentData = $students->map(function ($student) use ($studyYear, $subjects) {
            
            // جلب الدرجات السنوية
            $finalGrades = $student->finalYearGrades()
                ->where('study_year_id', $studyYear->id)
                ->get()->keyBy('subject_id');
            
            // جلب الدرجات الفصلية
            $termGrades = $student->termSubjectGrades()
                ->whereHas('courseOffering', fn($q) => $q->where('study_year_id', $studyYear->id))
                ->with('term:id,name')
                ->get()->groupBy('courseOffering.subject_id');

            return [
                'student_info' => [
                    'id' => $student->id,
                    'name' => $student->name,
                ],
                'final_grades' => $finalGrades,
                'term_grades' => $termGrades,
                // حساب المعدل العام للطالب
                'overall_average' => $finalGrades->avg('overall_numerical_score_percentage'),
                'promotion_status' => $finalGrades->first()->promotion_status ?? 'Not Set',
            ];
        });

        return response()->json([
            'class_info' => ['name' => $class->name, 'study_year_name' => $studyYear->name],
            'subjects' => $subjects->values(),
            'students_data' => $studentData,
        ]);
    }

      /**
     * --- الدالة الجديدة ---
     * جلب كل البيانات اللازمة لإنشاء شهادة نهاية العام لطالب واحد.
     * GET /api/final-grade-calculation/student-transcript
     */
    public function getStudentFinalTranscriptData(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $class = ClassRoom::with('studyYear.terms')->findOrFail($validated['class_id']);
        $student = Student::findOrFail($validated['student_id']);
        $studyYear = $class->studyYear;

        // 1. جلب جميع المقررات التي يدرسها الطالب
        $courseOfferings = $student->class->courseOfferings()
                            ->where('study_year_id', $studyYear->id)
                            ->with('subject:id,name')
                            ->get();

        // 2. جلب مقياس التقدير الكامل لهذه السنة الدراسية
        $gradingScales = GradingScale::where('study_year_id', $studyYear->id)
                                    ->orderBy('rank_order', 'desc')
                                    ->get(['grade_label', 'min_percentage', 'description']);
        
        // 3. تحديد درجة النجاح (نفترض أنها 50% كقيمة افتراضية إذا لم يتم تحديدها)
        $passingGrade = $gradingScales->where('description', 'Pass')->first();
        $minPassingScore = $passingGrade ? $passingGrade->min_percentage : 50.00;

        // 4. جلب كل درجات الطالب النهائية والسنوية لتسهيل البحث
        $termGrades = $student->termSubjectGrades()
            ->whereHas('courseOffering', fn($q) => $q->where('study_year_id', $studyYear->id))
            ->get()->groupBy('course_offering_id');
            
        $finalGrades = $student->finalYearGrades()
            ->where('study_year_id', $studyYear->id)
            ->get()->keyBy('subject_id');

        // 5. تجميع بيانات كل مادة
        $transcriptData = $courseOfferings->map(function ($course) use ($termGrades, $finalGrades, $minPassingScore, $studyYear) {
            $finalGradeData = $finalGrades->get($course->subject_id);
            $termGradesForCourse = $termGrades->get($course->id, collect());
            
            $term1 = $studyYear->terms->get(0);
            $term2 = $studyYear->terms->get(1);

            $term1Grade = $termGradesForCourse->firstWhere('term_id', $term1->id);
            $term2Grade = $termGradesForCourse->firstWhere('term_id', $term2->id);

            return [
                'subject_name' => $course->subject->name,
                'term1_score' => $term1Grade ? $term1Grade->weighted_average_score_percentage : null,
                'term2_score' => $term2Grade ? $term2Grade->weighted_average_score_percentage : null,
                'final_year_score' => $finalGradeData ? $finalGradeData->overall_numerical_score_percentage : null,
                'min_passing_score' => $minPassingScore,
                'subject_status' => ($finalGradeData && $finalGradeData->overall_numerical_score_percentage >= $minPassingScore) ? 'ناجح' : 'راسب',
            ];
        });

        // 6. تجميع بيانات الملخص النهائي
        $overallAverage = $finalGrades->avg('overall_numerical_score_percentage');
        $finalPromotionStatus = $overallAverage >= $minPassingScore ? 'ناجح إلى الصف التالي' : 'باقٍ للإعادة';

        return response()->json([
            'student_info' => $student->only(['id', 'name', 'srn']),
            'class_info' => $class->only(['id', 'name']),
            'study_year_info' => $studyYear->only(['id', 'name']),
            'transcript_data' => $transcriptData->values(),
            'summary' => [
                'overall_average' => round($overallAverage, 2),
                'final_promotion_status' => $finalPromotionStatus,
            ],
            'grading_scales' => $gradingScales, // <-- إضافة مقياس التقدير للرد
        ]);
    }


    public function getClassFinalTranscripts(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $class = ClassRoom::with('studyYear.terms', 'gradeLevel')->findOrFail($validated['class_id']);
        $studyYear = $class->studyYear;
        
        $gradingScales = GradingScale::where('study_year_id', $studyYear->id)
                                    ->orderBy('rank_order', 'asc')
                                    ->get(['grade_label', 'min_percentage', 'description']);

        $courseOfferings = $class->courseOfferings()
                                 ->where('study_year_id', $studyYear->id)
                                 ->with('subject:id,name')
                                 ->get();
        
        // --- التعديل الرئيسي هنا: إيجاد سياسة التقييم الأكثر تحديدًا ---
        $firstCourse = $courseOfferings->first();
        $gradingPolicy = $firstCourse ? $this->findApplicablePolicy($firstCourse, $class) : null;

        $subjects = $courseOfferings->pluck('subject')->unique('id')->sortBy('name');

        $students = Student::where('class_id', $class->id)
                           ->where('study_year_id', $studyYear->id)
                           ->orderBy('name')
                           ->get();
        
        $studentData = $students->map(function ($student) use ($studyYear, $subjects, $gradingScales) {
            
            $passingGrade = $gradingScales->firstWhere('description', 'Pass');
            $minPassingScore = $passingGrade ? $passingGrade->min_percentage : 50.00;

            $termGrades = $student->termSubjectGrades()
                ->whereHas('courseOffering', fn($q) => $q->where('study_year_id', $studyYear->id))
                ->with('term:id,name', 'courseOffering.subject:id,name')
                ->get()
                ->groupBy('courseOffering.subject_id'); 
                
            $finalGrades = $student->finalYearGrades()
                ->where('study_year_id', $studyYear->id)
                ->with('finalGradingScaleEntry:id,grade_label')
                ->get()->keyBy('subject_id');

            $overallAverage = $finalGrades->avg('overall_numerical_score_percentage');
            $finalPromotionStatus = $overallAverage >= $minPassingScore ? 'ناجح إلى الصف التالي' : 'باقٍ للإعادة';

            return [
                'student_info' => $student->only(['id', 'name', 'srn']),
                'final_grades' => $finalGrades,
                'term_grades' => $termGrades,
                'overall_average' => $overallAverage,
                'promotion_status' => $finalPromotionStatus,
            ];
        });

        return response()->json([
            'class_info' => $class->only(['id', 'name']),
            'study_year_info' => $studyYear->only(['id', 'name']),
            'subjects' => $subjects->values(),
            'students_data' => $studentData,
            'grading_scales' => $gradingScales,
            'grading_policy' => $gradingPolicy, // <-- إرسال السياسة التي تم العثور عليها
        ]);
    }


        /**
     * --- دالة مساعدة جديدة ---
     * البحث عن سياسة التقييم الأكثر تحديدًا لمقرر معين.
     */
    private function findApplicablePolicy(CourseOffering $course, ClassRoom $class)
    {
        return GradingPolicy::query()
            ->with('components.assessmentType:id,name')
            ->where('study_year_id', $course->study_year_id)
            ->where(function ($query) use ($course, $class) {
                $query->where('course_offering_id', $course->id)
                      ->orWhere(function ($q) use ($course, $class) {
                          $q->where('subject_id', $course->subject_id)
                            ->where('grade_level_id', $class->grade_level_id);
                      })
                      ->orWhere(function ($q) use ($class) {
                          $q->whereNull('subject_id')
                            ->where('grade_level_id', $class->grade_level_id);
                      })
                      ->orWhere(function ($q) use ($course) {
                          $q->where('subject_id', $course->subject_id)
                            ->whereNull('grade_level_id');
                      })
                      ->orWhere('is_default_for_school', true);
            })
            ->orderByRaw("CASE 
                            WHEN course_offering_id = ? THEN 0
                            WHEN subject_id = ? AND grade_level_id = ? THEN 1
                            WHEN grade_level_id = ? THEN 2
                            WHEN subject_id = ? THEN 3
                            WHEN is_default_for_school = 1 THEN 4
                            ELSE 5
                         END", 
                         [
                            $course->id, 
                            $course->subject_id, $class->grade_level_id,
                            $class->grade_level_id,
                            $course->subject_id
                         ])
            ->first();
    }
}
