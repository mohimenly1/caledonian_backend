<?php

// app/Http/Controllers/TeacherController.php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\StudyYear;


class TeacherController extends Controller
{
    public function index(Request $request)
    {
        // 1. نبدأ الاستعلام الأساسي لجلب المستخدمين من نوع "معلم"
        $query = User::where('user_type', 'teacher');

        // 2. التحقق من وجود كلمة بحث في الطلب وتطبيق الفلتر
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where('name', 'LIKE', '%' . $searchTerm . '%');
        }

        // 3. تحميل العلاقات المطلوبة بكفاءة، مع الترتيب الأبجدي وجلب الأعمدة الأساسية
        $teachers = $query->with([
            'teacherCourseAssignments.courseOffering.schoolClass:id,name',
            'teacherCourseAssignments.courseOffering.subject:id,name'
        ])
        ->orderBy('name', 'asc')
        ->get(['id', 'name']);

        // 4. المرور على كل معلم في القائمة لتجهيز بيانات الإسناد الخاصة به
        $teachers->each(function ($teacher) {
            // تجميع أسماء الفصول الفريدة
            $classes = $teacher->teacherCourseAssignments->map(function ($assignment) {
                return $assignment->courseOffering->schoolClass->name ?? null;
            })->filter()->unique()->implode(', ');

            // تجميع أسماء المواد الفريدة
            $subjects = $teacher->teacherCourseAssignments->map(function ($assignment) {
                return $assignment->courseOffering->subject->name ?? null;
            })->filter()->unique()->implode(', ');

            // إضافة الخصائص الجديدة لكائن المعلم
            $teacher->classes_taught = $classes;
            $teacher->subjects_taught = $subjects;

            // حذف العلاقة بعد معالجتها للحفاظ على حجم الاستجابة صغيرًا
            unset($teacher->teacherCourseAssignments);
        });

        // 5. إرجاع القائمة النهائية للمعلمين مع بياناتهم المجهزة
        return response()->json($teachers);
    }
    public function getTimetable()
{
    $teacher = Auth::user();

    if ($teacher->user_type !== 'teacher') {
        return response()->json([
            'success' => false,
            'message' => 'User is not a teacher'
        ], 403);
    }
    
    if (!$teacher) {
        return response()->json([
            'success' => false,
            'message' => 'Teacher not found'
        ], 404);
    }

    $timetable = Timetable::where('teacher_id', $teacher->id)
        ->with(['subject', 'class', 'section'])
        ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
        ->orderBy('start_time')
        ->get();

    return response()->json([
        'success' => true,
        'timetable' => $timetable
    ]);
}
}