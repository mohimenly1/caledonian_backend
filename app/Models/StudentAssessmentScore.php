<?php

/*

assessments:
المعلم يقوم بإنشاء "تقييم" جديد (واجب، اختبار، مشروع) ضمن "مقرر دراسي معروض" (course_offering_id) ولفصل دراسي معين (term_id).
يحدد نوع التقييم (assessment_type_id)، العنوان، الدرجة القصوى (max_score)، ومواعيد النشر والتسليم.
is_visible_to_students يتحكم في ظهور التقييم للطلاب، و are_grades_published يتحكم في ظهور الدرجات المرصودة لهم.
student_assessment_scores:
لكل "تقييم" تم إنشاؤه، ولكل طالب مسجل في ذلك المقرر، يمكن أن يكون هناك سجل في هذا الجدول.
يحتوي على الدرجة الفعلية (score_obtained) التي حصل عليها الطالب.
يتتبع معلومات التسليم والوقت الذي تم فيه الرصد ومن قام بالرصد.
عمود status مهم لتتبع حالة تسليم الطالب (هل سلم؟ هل تأخر؟ هل تم الرصد؟ هل هو غائب بعذر؟).
بهذين الجدولين، أصبح لدينا آلية مفصلة لإنشاء التقييمات ورصد درجات الطلاب فيها. الدرجات المسجلة هنا هي الدرجات "الخام" التي سيتم استخدامها لاحقًا لاحتساب المعدلات النهائية بناءً على سياسات التقييم (grading_policies).

الخطوة التالية ستكون تعديل جدول grading_scales (مقاييس التقدير) ليتناسب مع النظام الجديد، ثم إنشاء جداول لتخزين الدرجات النهائية المجمعة مثل student_term_subject_grades. هل أنت مستعد للمتابعة؟


*/


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAssessmentScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'student_id',
        'score_obtained',
        'submission_timestamp',
        'grading_timestamp',
        'graded_by_teacher_id',
        'teacher_remarks',
        'student_remarks',
        'submission_content_path',
        'status',
    ];

    protected $casts = [
        'score_obtained' => 'decimal:2',
        'submission_timestamp' => 'datetime',
        'grading_timestamp' => 'datetime',
    ];

    // Relationships
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function gradedByTeacher()
    {
        return $this->belongsTo(User::class, 'graded_by_teacher_id');
    }

    public function quizAnswers()
{
    return $this->hasMany(StudentQuizAnswer::class);
}
    // Accessor to get school_id via assessment for convenience
    // public function getSchoolIdAttribute()
    // {
    //     return $this->assessment->school_id ?? null;
    // }
}