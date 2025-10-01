<?php

// app/Models/GradingScale.php

/*

ملاحظة: تم استخدام min_percentage و max_percentage بدلاً من min_score و max_score المطلقة لتوفير مرونة أكبر، حيث أن الدرجات القصوى للتقييمات الفردية قد تختلف. سيتم تحويل درجة الطالب في التقييم إلى نسبة مئوية ثم مقارنتها بهذه الحدود.


*/
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingScale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'study_year_id',
        'grading_policy_id',
        'grade_label',
        'min_percentage',
        'max_percentage',
        'gpa_point',
        'description',
        'rank_order',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'gpa_point' => 'decimal:2',
    ];

  

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function gradingPolicy()
    {
        return $this->belongsTo(GradingPolicy::class);
    }

    public function studentTermSubjectGrades()
    {
        return $this->hasMany(StudentTermSubjectGrade::class, 'grading_scale_id');
    }

    public function studentFinalYearGrades()
    {
        return $this->hasMany(StudentFinalYearGrade::class, 'final_grading_scale_id');
    }
}