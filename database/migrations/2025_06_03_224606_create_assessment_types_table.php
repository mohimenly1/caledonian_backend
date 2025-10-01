<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_assessment_types_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assessment_types', function (Blueprint $table) {
            $table->id();
            // school_id: يجعل أنواع التقييمات خاصة بكل مدرسة،
            // أو يمكن جعله nullable إذا كانت هناك أنواع عامة على مستوى النظام وأنواع خاصة بالمدارس
            // $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., "Homework", "Quiz", "Midterm Exam"
            $table->text('description')->nullable();
            $table->unsignedInteger('default_max_score')->nullable()
            ->comment('الدرجة القصوى الافتراضية لهذا النوع، يمكن تعديلها');
        
        $table->boolean('is_summative')->default(true)
            ->comment('يساهم في حساب الدرجة النهائية');
        
        $table->boolean('requires_submission_file')->default(false)
            ->comment('هل يتطلب هذا النوع عادةً رفع ملف؟');
        
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name']); // Ensure name is unique within a school (or globally if school_id is null)
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessment_types');
    }
};