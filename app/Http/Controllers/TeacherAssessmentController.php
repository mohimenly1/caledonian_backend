<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\CourseOffering;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use App\Models\ClassRoom; // --- الإضافة هنا ---
use App\Models\Term; // --- الإضافة هنا ---
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AssessmentType; // --- الإضافة هنا ---
use Illuminate\Support\Facades\DB;



class TeacherAssessmentController extends Controller
{
    /**
     * Get all necessary data for the teacher's assessment dashboard.
     */
    public function getDashboardData()
    {
        $teacherUser = Auth::user();
    
        $activeStudyYear = StudyYear::where('is_active', true)->first();
        if (!$activeStudyYear) {
            return response()->json(['message' => 'No active study year found.'], 404);
        }
    
        $courses = CourseOffering::where('study_year_id', $activeStudyYear->id)
            ->whereHas('teachers', function ($query) use ($teacherUser) {
                $query->where('users.id', $teacherUser->id);
            })
            ->with(['subject:id,name', 'schoolClass:id,name'])
            ->get();
            
        $terms = Term::where('study_year_id', $activeStudyYear->id)->get(['id', 'name']);
        
        // ✨ --- 1. إضافة هذا السطر لجلب أنواع التقييمات --- ✨
        $assessmentTypes = AssessmentType::all(['id', 'name']);
    
        return response()->json([
            'courses' => $courses,
            'active_study_year' => $activeStudyYear,
            'terms' => $terms,
            'assessment_types' => $assessmentTypes, // ✨ <-- 2. إرسالها مع البيانات
        ]);
    }

    public function getDashboardDataForTeacher()
{
    $activeStudyYear = StudyYear::where('is_active', true)->first();
    if (!$activeStudyYear) {
        return response()->json(['message' => 'No active study year found.'], 404);
    }
    
    // جلب البيانات الأساسية
    $terms = Term::where('study_year_id', $activeStudyYear->id)->get(['id', 'name']);
    $assessmentTypes = AssessmentType::all(['id', 'name']); // ✨ الإضافة المهمة هنا ✨

    return response()->json([
        'active_study_year' => $activeStudyYear,
        'terms' => $terms,
        'assessment_types' => $assessmentTypes, // <-- إرسال أنواع التقييمات
    ]);
}

    /**
     * Get assessments created by the authenticated teacher, with filters.
     */
// لا تنسَ إضافة: use App\Models\Student; في أعلى الملف

public function getAssessments(Request $request)
{
    $teacherUser = Auth::user();

    $query = Assessment::where('created_by_teacher_id', $teacherUser->id)
                       ->with([
                           'courseOffering.subject:id,name',
                           'courseOffering.schoolClass:id,name',
                           'assessmentType:id,name',
                           'section:id,name',
                           'createdByTeacher:id,name' // ✨ --- الإضافة المهمة هنا --- ✨
                       ])
                       ->withCount('studentScores'); // نحتفظ بحساب الدرجات المرصودة فقط هنا

    if ($request->filled('class_id')) {
        $query->whereHas('courseOffering', function ($q) use ($request) {
            $q->where('class_id', $request->class_id);
        });
    }
    
    if ($request->filled('section_id')) {
        $query->where('section_id', $request->section_id);
    }
    
    // 1. جلب التقييمات مع ترقيم الصفحات
    $assessments = $query->latest()->paginate($request->input('per_page', 15));

    // ✨ --- 2. إضافة العدد الإجمالي للطلاب لكل تقييم على حدة --- ✨
    // نستخدم each للمرور على كل تقييم في الصفحة الحالية فقط
    $assessments->getCollection()->each(function ($assessment) {
        // لكل تقييم، نقوم بحساب عدد الطلاب في شعبته
        $studentCount = Student::where('section_id', $assessment->section_id)->count();
        // ثم نضيف هذا العدد إلى كائن التقييم
        $assessment->students_count = $studentCount;
    });
    // --- نهاية التعديل --- ✨

    return response()->json($assessments);
}
    /**
     * --- الدالة الجديدة هنا ---
     * Get a unique list of classes assigned to the authenticated teacher for the active study year.
     */
    public function getTeacherClasses()
    {
        $teacherUser = Auth::user();
        $activeStudyYear = StudyYear::where('is_active', true)->firstOrFail();

        // Get unique class IDs from the course offerings assigned to the teacher for the active year
        $classIds = CourseOffering::where('study_year_id', $activeStudyYear->id)
            ->whereHas('teachers', function ($query) use ($teacherUser) {
                $query->where('users.id', $teacherUser->id);
            })
            ->distinct()
            ->pluck('class_id');

 // --- التعديل الرئيسي هنا: جلب الأقسام مع الفصول ---
 $classes = ClassRoom::whereIn('id', $classIds)
 ->with('sections:id,name,class_id')
 ->get(['id', 'name']);

        return response()->json($classes);
    }
}
