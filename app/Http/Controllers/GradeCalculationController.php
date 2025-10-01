<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering; // تأكد من أن اسم النموذج صحيح، استخدمت 'Classes' بناءً على طلبك
use App\Models\ClassRoom;
use App\Models\Term;
use App\Models\Student;
use App\Models\GradingPolicy;
use App\Models\StudentTermSubjectGrade;
use App\Models\GradingScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class GradeCalculationController extends Controller
{
    /**
     * حساب وتخزين الدرجات النهائية لصف وفصل دراسي معين.
     * POST /api/grade-calculation/run
     */
    public function calculateAndStoreFinalGrades(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $classId = $validated['class_id'];
        $termId = $validated['term_id'];
        $class = ClassRoom::with('gradeLevel')->find($classId);

        $students = Student::where('class_id', $classId)
            ->where('study_year_id', $class->study_year_id)
            ->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students found for the selected class.'], 404);
        }

        $courseOfferings = $class->courseOfferings()->with('subject')->where('study_year_id', $class->study_year_id)->get();
        
        $gradingScale = GradingScale::where('study_year_id', $class->study_year_id)->get();
        if ($gradingScale->isEmpty()) {
             return response()->json(['message' => 'No grading scale found for the selected study year. Please define one first.'], 422);
        }

        DB::transaction(function () use ($students, $courseOfferings, $termId, $gradingScale, $class) {
            foreach ($students as $student) {
                foreach ($courseOfferings as $course) {

                    // --- التعديل هنا: تمرير الكائن $class بشكل صريح ---
                    $policy = $this->findApplicablePolicy($course, $class);
                    if (!$policy) {
                        continue;
                    }

                    $studentScores = $student->assessmentScores()
                        ->whereHas('assessment', function ($query) use ($course, $termId) {
                            $query->where('course_offering_id', $course->id)->where('term_id', $termId);
                        })->with('assessment.assessmentType')->get();

                    if ($studentScores->isEmpty()) {
                        continue;
                    }
                    
                    $finalScorePercentage = $this->calculateWeightedScore($policy, $studentScores);
                    $finalGrade = $this->getGradeFromScale($finalScorePercentage, $gradingScale);

                    StudentTermSubjectGrade::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'course_offering_id' => $course->id,
                            'term_id' => $termId,
                        ],
                        [
                            'weighted_average_score_percentage' => $finalScorePercentage,
                            'grading_scale_id' => $finalGrade ? $finalGrade->id : null,
                            'is_finalized' => true,
                            'finalized_timestamp' => now(),
                        ]
                    );
                }
            }
        });

        return response()->json(['message' => 'Final grades have been calculated successfully.']);
    }
    /**
     * البحث عن سياسة التقييم الأكثر تحديدًا لمقرر معين.
     */
    private function findApplicablePolicy(CourseOffering $course, ClassRoom $class)
    {
        // استخدام orderByRaw للبحث عن السياسة الأكثر تحديدًا في استعلام واحد
        return GradingPolicy::query()
            ->where('study_year_id', $course->study_year_id)
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
    /**
     * حساب الدرجة النهائية الموزونة بناءً على السياسة والدرجات المرصودة.
     */
    private function calculateWeightedScore(GradingPolicy $policy, Collection $scores)
    {
        $totalWeightedScore = 0;
        
        foreach ($policy->components as $component) {
            // فلترة درجات الطالب التي تنتمي لنوع التقييم الحالي في المكون
            $scoresForType = $scores->filter(function ($score) use ($component) {
                return $score->assessment->assessment_type_id == $component->assessment_type_id;
            });

            if ($scoresForType->isEmpty()) {
                continue; // لا توجد درجات لهذا النوع، انتقل للمكون التالي
            }

            // تحويل الدرجات إلى نسب مئوية
            $percentages = $scoresForType->map(function ($score) {
                if ($score->assessment->max_score > 0) {
                    return ($score->score_obtained / $score->assessment->max_score) * 100;
                }
                return 0;
            })->sort()->values(); // فرزها للتعامل مع "إسقاط الأدنى"

            // تطبيق قاعدة "إسقاط أقل درجة" إذا كانت مفعلة
            if ($component->drop_lowest_score && $percentages->count() > 1) {
                $percentages->shift(); // إزالة العنصر الأول (الأقل)
            }
            
            // حساب المتوسط لهذا المكون
            $averageForComponent = $percentages->avg();

            // إضافة الوزن النسبي للدرجة النهائية
            $totalWeightedScore += ($averageForComponent * $component->weight_percentage / 100);
        }

        return round($totalWeightedScore, 2);
    }

    /**
     * الحصول على التقدير المقابل من مقياس التقدير.
     */
    private function getGradeFromScale(float $percentage, Collection $scale)
    {
        return $scale->first(function ($grade) use ($percentage) {
            return $percentage >= $grade->min_percentage && $percentage <= $grade->max_percentage;
        });
    }


    // In app/Http/Controllers/Api/GradeCalculationController.php

public function getCalculationStatuses(Request $request)
{
    $validated = $request->validate([
        'study_year_id' => 'required|exists:study_years,id',
        'term_id' => 'required|exists:terms,id',
    ]);

    // جلب جميع الصفوف للسنة الدراسية المحددة
    $classes = ClassRoom::where('study_year_id', $validated['study_year_id'])->withCount('students')->get();

    $statuses = $classes->map(function ($class) use ($validated) {
        // تحقق مما إذا كان هناك أي سجل درجة نهائية معتمد لهذا الصف والفصل الدراسي
        $isFinalized = StudentTermSubjectGrade::where('term_id', $validated['term_id'])
            ->where('is_finalized', true)
            ->whereHas('courseOffering', function ($query) use ($class) {
                $query->where('class_id', $class->id);
            })->exists();

        return [
            'class_id' => $class->id,
            'class_name' => $class->name,
            'status_code' => $isFinalized ? 'Finalized' : 'Pending',
            'status_text' => $isFinalized ? 'تم اعتماد النتائج' : 'بانتظار الحساب والاعتماد',
            'student_count' => $class->students_count, // يأتي من withCount
        ];
    });

    return response()->json($statuses);
}
}
