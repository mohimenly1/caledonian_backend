<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // افترض أن المعلمين مخزنين في مودل User
use App\Models\TeacherCourseAssignment;
use App\Models\CourseOffering;
use App\Models\Subject;
use App\Models\ClassRoom; // أو Class، تأكد من الاسم الصحيح
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EduraTeacherController extends Controller
{

   /**
     * جلب إحصائيات المعلمين للمدرسة
     * هذا الـ Endpoint مخصص لنظام Edura.
     */
    public function getTeachersStats()
    {
        try {
            // 1. جلب إجمالي عدد المعلمين
            $totalTeachers = User::where('user_type', 'teacher')->count();

            // 2. جلب عدد المعلمين الذين لديهم مقررات مسندة
            $teachersWithAssignments = User::where('user_type', 'teacher')
                ->whereHas('teacherCourseAssignments')
                ->count();

            // 3. جلب إجمالي عدد المقررات المسندة
            $totalAssignments = TeacherCourseAssignment::count();

            // 4. حساب متوسط المقررات لكل معلم
            $averageAssignments = $totalTeachers > 0 ? round($totalAssignments / $totalTeachers, 1) : 0;

            // 5. توزيع المواد الدراسية
            $subjectsDistribution = DB::table('teacher_course_assignments')
                ->join('course_offerings', 'teacher_course_assignments.course_offering_id', '=', 'course_offerings.id')
                ->join('subjects', 'course_offerings.subject_id', '=', 'subjects.id')
                ->select('subjects.name as subject_name', DB::raw('COUNT(*) as count'))
                ->groupBy('subjects.name')
                ->get()
                ->pluck('count', 'subject_name')
                ->toArray();

            // 6. توزيع المعلمين على الفصول - استخدام اسم الجدول الصحيح 'classes'
            $classesDistribution = DB::table('teacher_course_assignments')
                ->join('course_offerings', 'teacher_course_assignments.course_offering_id', '=', 'course_offerings.id')
                ->join('classes', 'course_offerings.class_id', '=', 'classes.id') // ⭐ التصحيح هنا
                ->select('classes.name as class_name', DB::raw('COUNT(DISTINCT teacher_course_assignments.teacher_id) as teacher_count'))
                ->groupBy('classes.name')
                ->get()
                ->pluck('teacher_count', 'class_name')
                ->toArray();

            return response()->json([
                'total_teachers' => $totalTeachers,
                'teachers_with_assignments' => $teachersWithAssignments,
                'total_assignments' => $totalAssignments,
                'average_assignments_per_teacher' => $averageAssignments,
                'subjects_distribution' => $subjectsDistribution,
                'classes_distribution' => $classesDistribution,
                'last_updated' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTeachersStats: ' . $e->getMessage());
            
            // إرجاع بيانات افتراضية في حالة الخطأ
            return response()->json([
                'total_teachers' => 0,
                'teachers_with_assignments' => 0,
                'total_assignments' => 0,
                'average_assignments_per_teacher' => 0,
                'subjects_distribution' => [],
                'classes_distribution' => [],
                'last_updated' => now()->toDateTimeString(),
                'error' => 'Failed to fetch teachers statistics'
            ], 500);
        }
    }
    /**
     * جلب قائمة المعلمين مع المقررات والفصول والشعب المسندة إليهم.
     * هذا الـ Endpoint مخصص لنظام Edura.
     */
    public function getTeachersWithAssignments(Request $request)
    {
        // 1. جلب المعلمين (User) مع فلترة user_type
        $teachersQuery = User::where('user_type', 'teacher')
                        ->select('id', 'name', 'email', 'phone'); // جلب بيانات المعلم الأساسية فقط

        // 2. تحميل العلاقات المتداخلة بكفاءة (Eager Loading)
        $teachersQuery->with([
            // --- ⭐⭐ التصحيح 1: استخدام camelCase ليطابق اسم الدالة في مودل User ⭐⭐ ---
            'teacherCourseAssignments' => function ($query) {
                $query->select('id', 'teacher_id', 'course_offering_id', 'section_id')
                      ->with([
                          // من الإسناد، جلب الشعبة
                          'section:id,name', 
                          // --- ⭐⭐ التصحيح 2: استخدام camelCase ليطابق اسم الدالة في TeacherCourseAssignment ⭐⭐ ---
                          'courseOffering' => function ($q) {
                              $q->select('id', 'subject_id', 'class_id')
                                ->with([
                                    // من المقرر، جلب اسم المادة
                                    'subject:id,name', 
                                    // --- ⭐⭐ التصحيح 3: استخدام camelCase ليطابق اسم الدالة في CourseOffering ⭐⭐ ---
                                    'schoolClass:id,name' // (كانت 'class_room')
                                ]);
                          }
                      ]);
            }
        ]);

        // 3. تطبيق الـ Pagination
        $teachers = $teachersQuery->paginate($request->input('per_page', 15))->withQueryString();

        // 4. (اختياري) إعادة هيكلة البيانات لتكون أنظف لـ Edura
        $teachers->getCollection()->transform(function ($teacher) {
            // --- ⭐⭐ التصحيح 4: استخدام camelCase للوصول إلى العلاقة المحملة ⭐⭐ ---
            $teacher->assignments = $teacher->teacherCourseAssignments->map(function ($assignment) {
                return [
                    'assignment_id' => $assignment->id,
                    // --- ⭐⭐ التصحيح 5: استخدام camelCase للوصول إلى العلاقة ⭐⭐ ---
                    'subject_name' => $assignment->courseOffering->subject->name ?? 'مادة غير محددة',
                    // --- ⭐⭐ التصحيح 6: استخدام camelCase للوصول إلى العلاقة ⭐⭐ ---
                    'class_name' => $assignment->courseOffering->schoolClass->name ?? 'فصل غير محدد',
                    'section_name' => $assignment->section->name ?? 'شعبة غير محددة',
                ];
            });
            // --- ⭐⭐ التصحيح 7: استخدام camelCase للإلغاء ⭐⭐ ---
            unset($teacher->teacherCourseAssignments); // إزالة البيانات الأصلية المعقدة
            return $teacher;
        });

        return response()->json($teachers);
    }
}

