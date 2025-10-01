<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->date('date'); // Date of attendance
            $table->unsignedBigInteger('student_id'); // Foreign key for the student
            $table->unsignedBigInteger('class_id'); // Foreign key for the class
            $table->unsignedBigInteger('section_id'); // Foreign key for the section
            $table->enum('status', ['Present', 'Absent', 'Half day', 'Late', 'Has permission - Circumstance']);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
