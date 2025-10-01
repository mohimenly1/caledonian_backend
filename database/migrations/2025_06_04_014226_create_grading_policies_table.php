<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_grading_policies_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grading_policies', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., "Middle School General Policy 2024-2025", "Science Dept. Policy G10"
            $table->text('description')->nullable();
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade');

            // These allow a policy to be specific. Nullable means it can apply more broadly.
            // For example, a policy might be for a specific grade_level_id across all subjects,
            // or for a specific subject_id across all grade levels, or a combination.
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
            $table->foreignId('course_offering_id')->nullable()->constrained('course_offerings')->onDelete('cascade'); // For ultra-specific policy, less common

            $table->boolean('is_default_for_school')->default(false)->comment('Is this the default policy for the school if no other specific policy matches?');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grading_policies');
    }
};