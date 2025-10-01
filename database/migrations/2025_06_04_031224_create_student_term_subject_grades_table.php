<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_student_term_subject_grades_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/*

student_term_subject_grades (الدرجات النهائية المجمعة للطالب في المادة للفصل الدراسي)
الوظيفة: هذا الجدول مهم جدًا. سيخزن الدرجة النهائية المحسوبة للطالب في مقرر دراسي (course_offering_id) معين خلال فصل دراسي (term_id) محدد. هذه الدرجة يتم حسابها بناءً على درجات الطالب في التقييمات المختلفة (student_assessment_scores) وتطبيق سياسة التقييم (grading_policies) المناسبة.

*/

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_term_subject_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_offering_id')->constrained('course_offerings')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
        
            $table->decimal('weighted_average_score_percentage', 5, 2)->nullable()
                ->comment('النسبة المئوية المحسوبة كمتوسط مرجّح للتقييمات');
        
            $table->foreignId('grading_scale_id')->nullable()->constrained('grading_scales')->onDelete('set null')
                ->comment('الدرجة الحرفية حسب مقياس التقييم');
        
            $table->text('teacher_overall_remarks')->nullable()
                ->comment('ملاحظات المعلم العامة لهذا الفصل في هذه المادة');
        
            $table->unsignedInteger('rank_in_class')->nullable()
                ->comment('ترتيب الطالب في هذه المادة داخل الصف خلال الفصل الدراسي');
        
            $table->unsignedInteger('rank_in_section')->nullable()
                ->comment('ترتيب الطالب في هذه المادة داخل الشعبة خلال الفصل الدراسي');
        
            $table->boolean('is_finalized')->default(false)
                ->comment('هل تم اعتماد/قفل هذه الدرجة؟');
        
            $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('finalized_timestamp')->nullable();
        
            $table->timestamps();
            $table->softDeletes();
        
            $table->unique(['student_id', 'course_offering_id', 'term_id'], 'student_term_course_grade_unique');
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('student_term_subject_grades');
    }
};