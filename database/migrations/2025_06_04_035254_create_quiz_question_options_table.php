<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_quiz_question_options_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // الوظيفة: يخزن الخيارات المتاحة لأسئلة الاختيار من متعدد أو الصح والخطأ في الاختبار الإلكتروني.

        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->onDelete('cascade');
            $table->text('option_text');
            $table->boolean('is_correct_answer')->default(false);
            $table->unsignedInteger('order')->default(0)->comment('Order of the option');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_question_options');
    }
};