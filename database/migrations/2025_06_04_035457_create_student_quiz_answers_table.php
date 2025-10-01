<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_student_quiz_answers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_quiz_answers', function (Blueprint $table) {
            $table->id();

            // يرتبط بمحاولة الطالب أو درجاته لهذا الاختبار (التقييم)
            $table->foreignId('student_assessment_score_id')->constrained('student_assessment_scores')->onDelete('cascade');

            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->onDelete('cascade');

            // لاستخدامه في أسئلة الاختيار من متعدد أو صح/خطأ حيث يتم اختيار خيار معين
            $table->foreignId('quiz_question_option_id')->nullable()->constrained('quiz_question_options')->onDelete('cascade');

            // لاستخدامه في الأسئلة المقالية أو القصيرة
            $table->text('answer_text')->nullable();

            $table->boolean('is_marked_correct')->nullable()->comment('للأسئلة التي يمكن تصحيحها تلقائيًا مثل (الاختيارات أو صح/خطأ)');

            $table->decimal('points_awarded', 8, 2)->nullable()->comment('الدرجات الممنوحة لهذه الإجابة، خاصة في حال التصحيح اليدوي أو منح جزء من الدرجة');

            $table->timestamps();

            // لا حاجة للحذف الناعم هنا عادةً، لأن الإجابات جزء من سجل تقديم الاختبار
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_quiz_answers');
    }
};
