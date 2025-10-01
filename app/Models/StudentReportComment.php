<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentReportComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // 'school_id',
        'student_id',
        'study_year_id',
        'term_id',
        'comment_by_user_id',
        'commenter_role',
        'comment_type',
        'comment_text',
        'date_of_comment',
        'is_visible_on_report',
    ];

    protected $casts = [
        'date_of_comment' => 'date',
        'is_visible_on_report' => 'boolean',
    ];

    // public function school()
    // {
    //     return $this->belongsTo(School::class);
    // }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function commentedByUser()
    {
        return $this->belongsTo(User::class, 'comment_by_user_id');
    }
}



/*

student_report_comments (ملاحظات وتعليقات إضافية على الصحيفة)
الوظيفة: لتخزين التعليقات العامة التي قد يكتبها مربي الصف، أو مدير المدرسة، أو مرشد الطلاب على صحيفة الطالب، والتي تكون منفصلة عن ملاحظات المعلمين على المواد الدراسية.
ملف الترحيل (Migration):
*/