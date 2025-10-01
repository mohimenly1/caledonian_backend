<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_student_final_year_grades_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/*

student_final_year_grades (الدرجات النهائية المجمعة للطالب في السنة الدراسية)
الوظيفة: هذا الجدول اختياري إلى حد ما، حيث يمكن حساب هذه البيانات عند الحاجة. ولكنه مفيد لتخزين الدرجة السنوية النهائية للطالب في مادة معينة، والتي عادةً ما يتم حسابها من متوسط درجات الفصول الدراسية (student_term_subject_grades). يستخدم في تقارير نهاية العام وقرارات الترفيع.
*/
return new class extends Migration
{
    public function up()
    {
        Schema::create('student_final_year_grades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade'); // الدرجة لكل مادة خلال السنة
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade');

            // لتحديد السياق الصفّي في حال كان الطالب في أكثر من صف لنفس المادة (غير شائع ولكن لأجل الشمولية)
            // أو إذا لم تكن المادة مرتبطة بمقرر دراسي واحد طوال السنة (مثل المواد الاختيارية التي تتغير)
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('cascade'); // الصف الذي تنتمي إليه هذه الدرجة السنوية

            $table->decimal('overall_numerical_score_percentage', 5, 2)->nullable()
                ->comment('النسبة المئوية الإجمالية المحسوبة للسنة');

            $table->foreignId('final_grading_scale_id')->nullable()->constrained('grading_scales')->onDelete('set null')
                ->comment('الدرجة الحرفية النهائية للسنة');

            $table->text('final_remarks')->nullable()
                ->comment('الملاحظات النهائية للمادة خلال السنة');

            $table->enum('promotion_status', ['Not Set', 'Promoted', 'Conditionally Promoted', 'Retained', 'Completed'])
                ->default('Not Set')
                ->comment('حالة الطالب بخصوص هذه المادة/السنة أو السنة بشكل عام');

            $table->boolean('is_finalized')->default(false); // هل تم اعتماد الدرجة؟
            $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('finalized_timestamp')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // لا يسمح بتكرار نفس السجل لطالب، مادة، سنة دراسية، وصف
            $table->unique(['student_id', 'subject_id', 'study_year_id', 'class_id'], 'student_year_subject_class_grade_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_final_year_grades');
    }
};
