<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_quiz_questions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            // Ensure this assessment has is_online_quiz = true in application logic
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->text('question_text');
            $table->enum('question_type', [
                'multiple_choice_single_answer', // اختيار من متعدد إجابة واحدة
                'multiple_choice_multiple_answers', // اختيار من متعدد عدة إجابات
                'true_false', // صح أم خطأ
                'short_answer', // إجابة قصيرة (تحتاج تصحيح يدوي غالبًا)
                'essay' // مقالي (تحتاج تصحيح يدوي)
            ]);
            $table->decimal('points', 8, 2)->default(1.00)->comment('Points/score for this question');
            $table->unsignedInteger('order')->default(0)->comment('Order of the question in the quiz');
            $table->text('hint')->nullable(); // Optional hint for the question
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_questions');
    }
};