<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\TeacherCourseAssignment;

class TeacherDataController extends Controller
{
    // جلب الفصول الفريدة التي يدرسها معلم معين
    public function getTeacherClasses(User $teacher)
    {
        $classIds = TeacherCourseAssignment::where('teacher_id', $teacher->id)
            ->join('course_offerings', 'teacher_course_assignments.course_offering_id', '=', 'course_offerings.id')
            ->distinct()
            ->pluck('course_offerings.class_id');

        return ClassRoom::whereIn('id', $classIds)->get(['id', 'name']);
    }

    // جلب الشعب الفريدة التي يدرسها معلم في فصل معين
    public function getTeacherSectionsForClass(User $teacher, ClassRoom $classRoom)
    {
        $sectionIds = TeacherCourseAssignment::where('teacher_id', $teacher->id)
            ->where('section_id', '!=', null) // تأكد من وجود شعبة
            ->whereHas('courseOffering', function ($query) use ($classRoom) {
                $query->where('class_id', $classRoom->id);
            })
            ->distinct()
            ->pluck('section_id');
            
        return Section::whereIn('id', $sectionIds)->get(['id', 'name']);
    }

    // جلب المقررات التي يدرسها معلم في شعبة معينة
    public function getTeacherCoursesForSection(User $teacher, Section $section)
    {
        $courseOfferingIds = TeacherCourseAssignment::where('teacher_id', $teacher->id)
            ->where('section_id', $section->id)
            ->pluck('course_offering_id');

        return \App\Models\CourseOffering::with('subject:id,name')
            ->whereIn('id', $courseOfferingIds)
            ->get();
    }
}