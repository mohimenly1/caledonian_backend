<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TeacherCourseAssignment;
use App\Models\CourseOffering;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherCourseAssignmentController extends Controller
{
    /**
     * --- النسخة المحدثة ---
     * جلب قائمة المعلمين مع إمكانية البحث وعدد المقررات المسندة، مع دعم الترقيم.
     * GET /api/assignments/teachers
     */
    public function getTeachers(Request $request)
    {
        $query = User::where('user_type', 'teacher')
                     ->withCount('courses') // Assumes 'courses' relationship on User model
                     ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // --- التعديل الرئيسي هنا: استخدام paginate بدلاً من get ---
        $teachers = $query->paginate($request->input('per_page', 10));

        return response()->json($teachers);
    }

    /**
     * جلب المقررات المتاحة للإسناد مع الفلترة حسب السنة والفصل.
     * GET /api/assignments/available-courses
     */
    public function getAvailableCourses(Request $request)
    {
        $validated = $request->validate([
            'study_year_id' => 'required|exists:study_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id', // --- الإضافة هنا ---
            'teacher_id' => 'required|exists:users,id',
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);
        
        // الحصول على IDs المقررات المسندة حاليًا للمعلم في هذا القسم
        $assignedCourseIds = $teacher->teacherCourseAssignments()
            ->where('section_id', $validated['section_id'])
            ->pluck('course_offering_id');

        $query = CourseOffering::with(['subject:id,name'])
                               ->where('study_year_id', $validated['study_year_id'])
                               ->where('class_id', $validated['class_id'])
                               ->where('section_id', $validated['section_id']) // Filter by section
                               ->whereNotIn('id', $assignedCourseIds);

        return response()->json($query->get());
    }

    /**
     * عرض جميع المقررات المسندة لمعلم معين مع تفاصيلها الكاملة.
     */
    public function getTeacherAssignments(User $teacher)
    {
        
        if ($teacher->user_type !== 'teacher') {
            return response()->json(['message' => 'The selected user is not a teacher.'], 400);
        }

        // --- التعديل الرئيسي هنا: جلب تفاصيل القسم ---
        $assignments = $teacher->teacherCourseAssignments()->with([
            'courseOffering.subject:id,name',
            'courseOffering.schoolClass:id,name',
            'courseOffering.studyYear:id,name',
            'section:id,name' // Load the section details
        ])->get();

        return response()->json($assignments);
    }

    /**
     * إسناد أو تحديث جميع المقررات لمعلم معين.
     */
    public function syncTeacherAssignments(Request $request, User $teacher)
    {
       

        // --- التعديل الرئيسي هنا: استقبال مصفوفة من الكائنات ---
        $validated = $request->validate([
            'assignments' => 'present|array',
            'assignments.*.course_offering_id' => 'required|exists:course_offerings,id',
            'assignments.*.section_id' => 'required|exists:sections,id',
        ]);

        // Format data for sync with pivot
        $syncData = [];
        foreach ($validated['assignments'] as $assignment) {
            $syncData[$assignment['course_offering_id']] = ['section_id' => $assignment['section_id']];
        }

        $teacher->courses()->sync($syncData);

        return response()->json(['message' => 'Teacher assignments have been updated successfully.']);
    }
}