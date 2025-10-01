<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_course_offerings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            // Assuming you will create a 'schools' table for multi-school support
            // If you don't have a 'schools' table yet, you can add this column later
            // or make it nullable for now. For a multi-school system, it's crucial.
            // $table->foreignId('school_id')->constrained('schools')->onDelete('cascade'); // New for multi-school

            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade'); // User confirmed 'classes' table name
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade'); // Can be nullable if subject is for whole class
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade');

            // Optional: You might add a specific name or code for this offering if needed,
            // e.g., "Math-10A-2024 (Advanced)"
            // $table->string('offering_code')->unique()->nullable();
            // $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Ensure a unique combination for a given school
            $table->unique([
                // 'school_id',
                'subject_id',
                'class_id',
                'section_id',
                'study_year_id'
            ], 'school_course_offering_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_offerings');
    }
};