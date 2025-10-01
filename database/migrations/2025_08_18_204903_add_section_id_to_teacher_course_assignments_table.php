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
        Schema::table('teacher_course_assignments', function (Blueprint $table) {
            // Add section_id, making it nullable for flexibility
            $table->foreignId('section_id')->nullable()->constrained('sections')->after('course_offering_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_course_assignments', function (Blueprint $table) {
            //
        });
    }
};
