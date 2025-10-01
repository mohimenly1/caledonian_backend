<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAttendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'student_id',
        'timetable_id', // Links to the scheduled period
        'attendance_date',
        'term_id',
        'status',
        'remarks',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    // Relationships
    // public function school()
    // {
    //     return $this->belongsTo(School::class);
    // }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function timetableEntry() // The specific scheduled class/period
    {
        return $this->belongsTo(Timetable::class, 'timetable_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function recordedByUser() // User who recorded attendance
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    // Convenience accessors to get subject, class, section, teacher via timetable_id
    public function getSubjectAttribute()
    {
        return $this->timetableEntry->subject ?? null;
    }

    public function getSchoolClassAttribute()
    {
        return $this->timetableEntry->schoolClass ?? null;
    }

    public function getSectionAttribute()
    {
        return $this->timetableEntry->section ?? null;
    }

    public function getTeacherAttribute()
    {
        return $this->timetableEntry->teacher ?? null;
    }
}



/*



شرح إضافي:

timetables:
يجب أن يحتوي على school_id إذا كان نظامك متعدد المدارس.
يحدد كل حصة (مادة، معلم، وقت، يوم، صف، شعبة).
عمود specific_date مفيد للحصص غير المتكررة أو لتعديل حصة متكررة في يوم معين.
student_attendance:
يربط الطالب (student_id) بحصة مجدولة معينة (timetable_id) في تاريخ محدد (attendance_date). هذا يسمح بتسجيل الحضور لكل حصة.
إذا كنت تحتاج أيضًا إلى تسجيل حضور يومي عام (ليس لكل حصة)، قد تحتاج إلى جدول منفصل أو طريقة أخرى لتمييز هذا النوع من سجلات الحضور (مثلاً، timetable_id يكون NULL وحقول أخرى مثل section_id تحدد السياق). ولكن الربط بـ timetable_id يوفر تفصيلاً أكبر وهو شائع في أنظمة إدارة المدارس.
term_id يساعد في تجميع تقارير الحضور لكل فصل دراسي.
بهذين الجدولين، نكون قد غطينا الجوانب الأساسية للجداول الزمنية وتسجيل الحضور والغياب. هذا يكمل الهيكل الرئيسي للجزء الأكاديمي الذي ناقشناه.

الخطوات التالية المحتملة (خارج النطاق الحالي ولكن للتفكير المستقبلي):

جدول schools: إذا لم يكن موجودًا، فهو ضروري لنظام متعدد المدارس.
الوحدات الدراسية (Units/Chapters): تفصيل المواد إلى وحدات أو فصول دراسية وربط التقييمات والمواد التعليمية بها.
المهارات والكفاءات (Skills/Competencies): تحديد المهارات وربطها بالمواد والتقييمات لتتبع تقدم الطلاب فيها.
التواصل (Communication): جداول للرسائل والإعلانات بين المعلمين والطلاب وأولياء الأمور.
التقارير المخصصة (Custom Reports): قد تحتاج إلى جداول لتخزين تعريفات التقارير المخصصة.

*/