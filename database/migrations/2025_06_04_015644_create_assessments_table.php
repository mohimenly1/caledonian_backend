<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_assessments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('assessment_type_id')->constrained('assessment_types')->onDelete('cascade');
            $table->foreignId('created_by_teacher_id')->constrained('users')->onDelete('cascade'); // User ID of the teacher who created it

            $table->string('title'); // e.g., "Chapter 1 Homework", "Midterm Exam - Algebra"
            $table->text('description')->nullable(); // Instructions, details
            $table->decimal('max_score', 8, 2); // Maximum possible score for this assessment

            $table->dateTime('publish_date_time')->nullable()
            ->comment('متى يصبح التقييم مرئيًا للطلاب');
        
        $table->dateTime('due_date_time')->nullable()
            ->comment('الموعد النهائي لتسليم الطلاب');
        
        $table->dateTime('grading_due_date_time')->nullable()
            ->comment('اختياري: الموعد النهائي للمعلمين لإكمال التصحيح');
        
        $table->boolean('is_online_quiz')->default(false)
            ->comment('صحيح إذا كان هذا التقييم مرتبطًا باختبار إلكتروني');
        
        $table->boolean('is_visible_to_students')->default(false)
            ->comment('هل هذا التقييم مرئي للطلاب؟');
        
        $table->boolean('are_grades_published')->default(false)
            ->comment('هل الدرجات لهذا التقييم مرئية للطلاب؟');
        

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessments');
    }
};