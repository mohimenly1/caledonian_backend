<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\Term;
use App\Models\CourseOffering;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PortalController extends Controller
{
    /**
     * جلب قائمة أبناء ولي الأمر مع study_year_id.
     * GET /api/parent/my-children
     */
    public function getMyChildren()
    {
        $user = Auth::user();

        if ($user->user_type !== 'parent' || !$user->parentProfile) {
            return response()->json(['success' => false, 'message' => 'User is not a parent.'], 403);
        }

        $children = $user->parentProfile->students()->with(['class:id,name', 'section:id,name'])->get();

        $formattedChildren = $children->map(function($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'arabic_name' => $student->arabic_name,
                'class' => $student->class->name ?? 'N/A',
                'section' => $student->section->name ?? 'N/A',
                'photo' => $student->photo ? url('storage/' . $student->photo) : null,
                'srn' => $student->srn,
                'gender' => $student->gender,
                'study_year_id' => $student->study_year_id, // --- الإضافة الأهم هنا ---
                'study_year_name' => $student->studyYear->name ?? 'N/A', // --- الإضافة الأهم هنا ---
            ];
        });

        return response()->json([
            'success' => true,
            'children' => $formattedChildren
        ]);
    }

    /**
     * جلب الفصول الدراسية لسنة دراسية محددة.
     * GET /api/study-years/{studyYear}/terms
     */
    public function getTermsForStudyYear(StudyYear $studyYear)
    {
        return response()->json($studyYear->terms()->get());
    }

    /**
     * جلب كل البيانات الأكاديمية اللازمة لطالب معين في فصل دراسي معين.
     * GET /api/students/{student}/dashboard
     */
    public function getStudentDashboardData(Request $request, Student $student)
    {

        $validated = $request->validate([
            'term_id' => 'required|exists:terms,id',
        ]);

        $finalGrades = $student->termSubjectGrades()
            ->where('term_id', $validated['term_id'])
            ->where('is_finalized', true)
            ->with(['courseOffering.subject:id,name', 'gradingScaleEntry:id,grade_label'])
            ->get();

        return response()->json($finalGrades);
    }

    /**
     * جلب جميع درجات الطالب (معتمدة وغير معتمدة) لأولياء الأمور
     * GET /api/students/{student}/grades-for-parent
     *
     * يجلب الدرجات من termSubjectGrades إذا كانت موجودة،
     * وإلا يحسبها من assessmentScores مباشرة
     */
    public function getStudentGradesForParent(Request $request, Student $student)
    {
        $validated = $request->validate([
            'term_id' => 'required|exists:terms,id',
        ]);

        \Log::info('[PortalController@getStudentGradesForParent] Starting', [
            'student_id' => $student->id,
            'term_id' => $validated['term_id'],
            'class_id' => $student->class_id,
            'section_id' => $student->section_id,
            'study_year_id' => $student->study_year_id,
        ]);

        // ✅ 1. جلب الدرجات من termSubjectGrades (إن وجدت)
        $termGrades = $student->termSubjectGrades()
            ->where('term_id', $validated['term_id'])
            ->with([
                'courseOffering.subject:id,name',
                'gradingScaleEntry:id,grade_label'
            ])
            ->get();

        \Log::info('[PortalController@getStudentGradesForParent] Term grades count', [
            'student_id' => $student->id,
            'term_id' => $validated['term_id'],
            'term_grades_count' => $termGrades->count(),
        ]);

        // ✅ 2. إذا كانت هناك درجات معتمدة، نعيدها مباشرة
        if ($termGrades->isNotEmpty()) {
            \Log::info('[PortalController@getStudentGradesForParent] Returning term grades', [
                'student_id' => $student->id,
                'count' => $termGrades->count(),
            ]);
            return response()->json($termGrades);
        }

        // ✅ 3. إذا لم تكن هناك درجات معتمدة، نحسبها من assessmentScores
        // جلب جميع المقررات التي يدرسها الطالب
        $courseOfferings = CourseOffering::where('class_id', $student->class_id)
            ->where('section_id', $student->section_id)
            ->where('study_year_id', $student->study_year_id)
            ->with('subject:id,name')
            ->get();

        \Log::info('[PortalController@getStudentGradesForParent] Course offerings', [
            'student_id' => $student->id,
            'course_offerings_count' => $courseOfferings->count(),
            'course_offerings' => $courseOfferings->pluck('id')->toArray(),
        ]);

        $calculatedGrades = [];

        foreach ($courseOfferings as $course) {
            // جلب جميع درجات التقييمات للطالب في هذا المقرر
            $assessmentScores = $student->assessmentScores()
                ->whereHas('assessment', function ($query) use ($course, $validated) {
                    $query->where('course_offering_id', $course->id)
                          ->where('term_id', $validated['term_id']);
                })
                ->with('assessment.assessmentType')
                ->get();

            \Log::info('[PortalController@getStudentGradesForParent] Assessment scores for course', [
                'student_id' => $student->id,
                'course_offering_id' => $course->id,
                'subject_name' => $course->subject->name ?? 'N/A',
                'assessment_scores_count' => $assessmentScores->count(),
            ]);

            if ($assessmentScores->isEmpty()) {
                continue; // لا توجد درجات لهذا المقرر
            }

            // ✅ حساب الدرجة النهائية من assessmentScores
            // نحسب المتوسط البسيط للدرجات (يمكن تحسينه لاحقاً لاستخدام سياسة التقييم)
            $totalScore = 0;
            $totalMax = 0;

            foreach ($assessmentScores as $score) {
                $totalScore += $score->score_obtained ?? 0;
                $totalMax += $score->assessment->max_score ?? 0;
            }

            $percentage = $totalMax > 0 ? ($totalScore / $totalMax) * 100 : 0;

            \Log::info('[PortalController@getStudentGradesForParent] Calculated grade', [
                'student_id' => $student->id,
                'course_offering_id' => $course->id,
                'subject_name' => $course->subject->name ?? 'N/A',
                'total_score' => $totalScore,
                'total_max' => $totalMax,
                'percentage' => $percentage,
            ]);

            // ✅ إنشاء كائن مشابه لـ termSubjectGrades
            $calculatedGrades[] = [
                'id' => null, // ليس له ID لأنه غير محفوظ في قاعدة البيانات
                'student_id' => $student->id,
                'course_offering_id' => $course->id,
                'term_id' => $validated['term_id'],
                'weighted_average_score_percentage' => round($percentage, 2),
                'grading_scale_id' => null,
                'is_finalized' => false,
                'course_offering' => [
                    'id' => $course->id,
                    'subject' => [
                        'id' => $course->subject->id ?? null,
                        'name' => $course->subject->name ?? 'N/A',
                    ],
                ],
                'grading_scale_entry' => null,
            ];
        }

        \Log::info('[PortalController@getStudentGradesForParent] Returning calculated grades', [
            'student_id' => $student->id,
            'calculated_grades_count' => count($calculatedGrades),
        ]);

        return response()->json($calculatedGrades);
    }

       /**
     * جلب تفاصيل تقييمات الطالب في مقرر معين.
     * GET /api/students/{student}/subjects/{courseOffering}/details
     */
    public function getSubjectDetails(Request $request, Student $student, CourseOffering $courseOffering)
    {
        $validated = $request->validate([
            'term_id' => 'required|exists:terms,id'
        ]);

        $assessments = $student->assessmentScores()
            ->whereHas('assessment', function ($q) use ($courseOffering, $validated) {
                $q->where('course_offering_id', $courseOffering->id)
                  ->where('term_id', $validated['term_id']);
            })
            ->with('assessment:id,title,max_score,assessment_type_id', 'assessment.assessmentType:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($assessments);
    }
    public function getTeachersForChild(Student $student)
    {
        $courseOfferings = $student->class
            ->courseOfferings()
            ->where('study_year_id', $student->study_year_id)
            ->with(['teachers' => function ($query) {
                // جلب كل البيانات اللازمة للمعلم من البداية
                $query->select('users.id', 'name', 'photo', 'last_activity', 'email', 'phone');
            }, 'subject:id,name'])
            ->get();

        $teachersCollection = new Collection();

        // تجميع المواد تحت كل معلم فريد
        foreach ($courseOfferings as $course) {
            foreach ($course->teachers as $teacher) {
                if (!$teachersCollection->has($teacher->id)) {
                    $teacher->setRelation('subjects', new Collection());
                    $teachersCollection->put($teacher->id, $teacher);
                }
                $teachersCollection->get($teacher->id)->subjects->push(['subject_name' => $course->subject->name]);
            }
        }

        // --- التعديل الرئيسي هنا: إضافة البيانات المفقودة من دالتك القديمة ---
        $finalTeachersList = $teachersCollection->values()->map(function(User $teacher) {
            // تحويل كائن المعلم الأساسي إلى مصفوفة
            $teacherData = $teacher->toArray();

            // إضافة الحقول الديناميكية من نموذج User
            $teacherData['is_online'] = $teacher->isOnline(); // استخدام الدالة من النموذج
            $teacherData['last_seen'] = $teacher->lastSeen();
            $teacherData['can_chat'] = true; // افترض أن الدردشة متاحة دائمًا الآن

            // التأكد من استخدام photo_url
            $teacherData['photo_url'] = $teacher->photo_url;

            // إزالة علاقة pivot غير الضرورية
            unset($teacherData['pivot']);
            return $teacherData;
        });

        return response()->json([
            'success' => true,
            'student' => ['name' => $student->name],
            'teachers' => $finalTeachersList,
        ]);
    }


    public function getStudentAcademicDashboard(Request $request, Student $student)
    {
        $validated = $request->validate([
            'term_id' => 'required|exists:terms,id',
        ]);

        // 1. جلب جميع المقررات التي يدرسها الطالب في سنته الحالية
        $courseOfferings = CourseOffering::where('class_id', $student->class_id)
        ->where('section_id', $student->section_id) // <-- السطر الجديد والمهم
        ->where('study_year_id', $student->study_year_id)
        ->with('subject:id,name')
        ->get();

        // 2. جلب جميع الدرجات النهائية المتاحة للطالب في هذا الفصل الدراسي
        $finalGrades = $student->termSubjectGrades()
            ->where('term_id', $validated['term_id'])
            ->where('is_finalized', true)
            ->with('gradingScaleEntry:id,grade_label')
            ->get()
            ->keyBy('course_offering_id'); // استخدام ID المقرر كمفتاح لتسهيل البحث

        // 3. المرور على كل المقررات ودمجها مع الدرجة النهائية إن وجدت
        $dashboardData = $courseOfferings->map(function ($course) use ($finalGrades) {
            $finalGradeData = $finalGrades->get($course->id);

            return [
                'course_offering_id' => $course->id,
                'subjectName' => data_get($course, 'subject.name', 'N/A'),
                'finalScore' => data_get($finalGradeData, 'weighted_average_score_percentage'),
                'gradeLabel' => data_get($finalGradeData, 'gradingScaleEntry.grade_label'),
            ];
        });

        return response()->json($dashboardData);
    }

}
