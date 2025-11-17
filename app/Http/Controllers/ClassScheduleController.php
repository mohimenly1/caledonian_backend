<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\TeacherCourseAssignment;
use App\Models\Term;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    /**
     * جلب الجدول الزمني لشعبة معينة.
     */
    public function index(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);
        
        $section = Section::findOrFail($request->section_id);
        $classId = $section->class_id;

        $schedules = ClassSchedule::where('term_id', Term::where('is_active', true)->first()->id ?? null)
            ->whereHas('teacherCourseAssignment.courseOffering', function ($query) use ($request, $classId) {
                $query->where('class_id', $classId)
                      ->where(function ($q) use ($request) {
                          $q->where('section_id', $request->section_id)
                            ->orWhereNull('section_id');
                      });
            })
            ->with([
                'teacherCourseAssignment.courseOffering.subject:id,name',
                'teacherCourseAssignment.teacher:id,name'
            ])
            ->get();
            
        return response()->json($schedules);
    }

    /**
     * إضافة حصة جديدة في الجدول.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_course_assignment_id' => 'required|exists:teacher_course_assignments,id',
            'schedule_period_id' => 'required|exists:schedule_periods,id',
            'day_of_week' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
        ]);

        $activeTerm = Term::where('is_active', true)->first();
        if (!$activeTerm) {
            return response()->json(['message' => 'لا يوجد فصل دراسي نشط في النظام حالياً. يرجى تفعيل فصل دراسي أولاً.'], 400);
        }

        $assignment = TeacherCourseAssignment::with('courseOffering.schoolClass')->findOrFail($validated['teacher_course_assignment_id']);
        $teacherId = $assignment->teacher_id;
        
        // التحقق من تعارض المعلم (لا تغيير هنا، هذا الكود سليم)
        $teacherConflict = ClassSchedule::where('day_of_week', $validated['day_of_week'])
            ->where('schedule_period_id', $validated['schedule_period_id'])
            ->where('term_id', $activeTerm->id)
            ->whereHas('teacherCourseAssignment', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })->exists();
        
        if ($teacherConflict) {
            return response()->json(['message' => 'هذا المعلم لديه حصة أخرى في نفس التوقيت.'], 409);
        }

        // ⭐⭐ التعديل الرئيسي هنا: إصلاح منطق التحقق من تعارض الشعبة ⭐⭐
        $targetSectionId = $assignment->courseOffering->section_id;

        $sectionConflict = ClassSchedule::where('day_of_week', $validated['day_of_week'])
            ->where('schedule_period_id', $validated['schedule_period_id'])
            ->where('term_id', $activeTerm->id)
            ->whereHas('teacherCourseAssignment.courseOffering', function ($query) use ($targetSectionId) {
                // ابحث عن حصة موجودة بالفعل تكون إما:
                // 1. مسجلة لنفس الشعبة التي نحاول الإضافة إليها.
                // 2. أو مسجلة للفصل بأكمله (بدون شعبة محددة).
                $query->where(function ($q) use ($targetSectionId) {
                    $q->where('section_id', $targetSectionId)
                      ->orWhereNull('section_id');
                });
            })->exists();

        if ($sectionConflict) {
            return response()->json(['message' => 'هذه الشعبة لديها حصة أخرى في نفس التوقيت.'], 409);
        }

        $dataToCreate = array_merge($validated, ['term_id' => $activeTerm->id]);
        $schedule = ClassSchedule::create($dataToCreate);
        
        $schedule->load([
            'teacherCourseAssignment.courseOffering.subject:id,name',
            'teacherCourseAssignment.teacher:id,name'
        ]);
        
        return response()->json($schedule, 201);
    }
    /**
     * حذف حصة من الجدول.
     */
    public function destroy(ClassSchedule $classSchedule)
    {
        $classSchedule->delete();
        return response()->json(null, 204);
    }
    
    /**
     * جلب كل البيانات المطلوبة لواجهة الطباعة.
     */
    public function getPrintableData(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id']);
        $sectionId = $request->section_id;
        $activeTerm = Term::where('is_active', true)->first();

        $section = Section::with(['class.studyYear', 'class.gradeLevel'])->findOrFail($sectionId);
        $periods = SchedulePeriod::orderBy('start_time')->get();
        
        $query = ClassSchedule::whereHas('teacherCourseAssignment.courseOffering', function ($query) use ($sectionId) {
            $query->where('section_id', $sectionId)->orWhereNull('section_id');
        })->with([
            'teacherCourseAssignment.courseOffering.subject:id,name',
            'teacherCourseAssignment.teacher:id,name'
        ]);
        
        if ($activeTerm) {
            $query->where('term_id', $activeTerm->id);
        }
        
        return response()->json([
            'section' => $section,
            'periods' => $periods,
            'schedule' => $query->get(),
        ]);
    }
}