<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\Term;
use App\Models\SchedulePeriod;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use ArPHP\I18N\Arabic;

class ParentScheduleController extends Controller
{
    /**
     * جلب الجدول الزمني لشعبة معينة.
     */
    public function getStudentTimetable(Student $student)
    {
        // ⭐⭐ التعديل الرئيسي هنا: إضافة التحقق الآمن ⭐⭐
        $activeTerm = Term::where('is_active', true)->first();

        // إذا لم يكن هناك فصل دراسي نشط، أرجع مصفوفة فارغة
        if (!$activeTerm) {
            return response()->json([]);
        }

        $sectionId = $student->section_id;
        $classId = $student->class_id;
        
        if (!$sectionId || !$classId) {
            return response()->json([]);
        }

        $schedules = ClassSchedule::where('term_id', $activeTerm->id) // استخدام المتغير الآمن
            ->whereHas('teacherCourseAssignment.courseOffering', function ($query) use ($sectionId, $classId) {
                $query->where('class_id', $classId)
                      ->where(function ($q) use ($sectionId) {
                          $q->where('section_id', $sectionId)
                            ->orWhereNull('section_id');
                      });
            })
            ->with([
                'schedulePeriod:id,name,start_time,end_time',
                'teacherCourseAssignment.courseOffering.subject:id,name',
                'teacherCourseAssignment.teacher:id,name'
            ])
            ->get();
        
        // إعادة تنظيم البيانات قبل إرسالها
        $structured = [];
        foreach ($schedules as $entry) {
            $day = $entry->day_of_week;
            $structured[$day][] = [
                'period_name' => optional($entry->schedulePeriod)->name,
                'start_time'  => date('H:i', strtotime(optional($entry->schedulePeriod)->start_time)),
                'end_time'    => date('H:i', strtotime(optional($entry->schedulePeriod)->end_time)),
                'subject'     => optional(optional(optional($entry->teacherCourseAssignment)->courseOffering)->subject)->name,
                'teacher'     => optional(optional($entry->teacherCourseAssignment)->teacher)->name,
            ];
        }
            
        return response()->json($structured);
    }

    /**
     * جلب الجدول الدراسي لطالب معين وتنزيله كملف PDF.
     */
    public function downloadStudentTimetable(Student $student)
    {
        $arabic = new Arabic('Glyphs');
        
        // جلب الهيكل الكامل لليوم الدراسي (جميع الفترات مرتبة)
        $allPeriods = SchedulePeriod::orderBy('start_time')->get();

        // جلب الحصص المسجلة للطالب
        $structuredTimetable = $this->fetchAndStructureTimetable($student);

        // ⭐⭐ 1. إنشاء متغير labels$ المفقود ومعالجة نصوصه ⭐⭐
        $labels = [
            'title'         => $arabic->utf8Glyphs('الجدول الدراسي'),
            'student_label' => $arabic->utf8Glyphs('للطالب: '),
            'period_label'  => $arabic->utf8Glyphs('الفترة'),
            'break_label'   => $arabic->utf8Glyphs('فـــــســـــحـــــة'),
        ];

        // ⭐⭐ 2. إنشاء متغير student$ المفقود ومعالجة نصوصه ⭐⭐
        $studentData = [
            'studentName' => $arabic->utf8Glyphs($student->name),
            'className'   => $arabic->utf8Glyphs(optional($student->class)->name ?? 'غير مسجل'),
            'sectionName' => $arabic->utf8Glyphs(optional($student->section)->name ?? ''),
        ];

        // معالجة أيام الأسبوع
        $weekdays = [
            'sunday'    => $arabic->utf8Glyphs('الأحد'),
            'monday'    => $arabic->utf8Glyphs('الاثنين'),
            'tuesday'   => $arabic->utf8Glyphs('الثلاثاء'),
            'wednesday' => $arabic->utf8Glyphs('الأربعاء'),
            'thursday'  => $arabic->utf8Glyphs('الخميس')
        ];

        // ⭐⭐ 3. تجميع كل البيانات في متغير data$ لإرسالها للـ View ⭐⭐
        $data = [
            'labels'      => $labels,
            'student'     => $studentData,
            'weekdays'    => $weekdays,
            'periods'     => $allPeriods, // إرسال الهيكل الكامل للفترات
            'timetable'   => $structuredTimetable, // إرسال الحصص المسجلة
        ];

        $pdf = Pdf::loadView('pdfs.timetable', $data);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('جدول-حصص-' . $student->name . '.pdf');
    }
    /**
     * دالة مساعدة لجلب البيانات الخام للجدول.
     */
    private function fetchAndStructureTimetable(Student $student)
    {
        $activeTerm = Term::where('is_active', true)->first();
        if (!$activeTerm) return [];
    
        $sectionId = $student->section_id; 
        $classId = $student->class_id;
    
        if (!$sectionId || !$classId) return [];
    
        $scheduleEntries = ClassSchedule::where('term_id', $activeTerm->id)
            ->whereHas('teacherCourseAssignment.courseOffering', function ($query) use ($sectionId, $classId) {
                $query->where('class_id', $classId)
                      ->where(function ($q) use ($sectionId) {
                          $q->where('section_id', $sectionId)->orWhereNull('section_id');
                      });
            })
            ->with([
                'schedulePeriod:id,name,start_time,end_time',
                'teacherCourseAssignment.courseOffering.subject:id,name',
                'teacherCourseAssignment.teacher:id,name'
            ])
            ->get();
    
        $structured = [];
        foreach ($scheduleEntries as $entry) {
            $day = $entry->day_of_week;
            $structured[$day][] = [
                'period_id'   => optional($entry->schedulePeriod)->id,
                'period_name' => optional($entry->schedulePeriod)->name,
                'subject'     => optional(optional(optional($entry->teacherCourseAssignment)->courseOffering)->subject)->name,
                'teacher'     => optional(optional($entry->teacherCourseAssignment)->teacher)->name,
            ];
        }
        
        return $structured;
    }
}