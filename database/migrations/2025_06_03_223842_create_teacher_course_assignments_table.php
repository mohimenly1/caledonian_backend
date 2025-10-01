<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_teacher_course_assignments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teacher_course_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade'); // User with user_type 'teacher'
            $table->foreignId('course_offering_id')->constrained('course_offerings')->onDelete('cascade');
            $table->string('role')->default('Primary Teacher'); // e.g., "Primary Teacher", "Assistant", "Substitute"
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['teacher_id', 'course_offering_id', 'role'], 'teacher_course_role_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('teacher_course_assignments');
    }
};