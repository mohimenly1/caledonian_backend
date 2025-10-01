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
