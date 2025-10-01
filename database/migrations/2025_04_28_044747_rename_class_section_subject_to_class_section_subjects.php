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
        Schema::table('class_section_subjects', function (Blueprint $table) {
            Schema::rename('class_section_subject', 'class_section_subjects');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_section_subjects', function (Blueprint $table) {
            Schema::rename('class_section_subjects', 'class_section_subject');
        });
    }
};
