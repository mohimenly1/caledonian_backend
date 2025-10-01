<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('student_parent_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade'); // Ensure 'parents' table uses ParentModel
            $table->string('relationship_type'); // e.g., Father, Mother, Guardian
            $table->timestamps();

            $table->unique(['student_id', 'parent_id', 'relationship_type'], 'student_parent_unique');            // Ensure unique combination
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_parent_relationships');
    }
};
