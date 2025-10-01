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
 // database/migrations/[timestamp]_create_class_section_subject_table.php
Schema::create('class_section_subject', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('class_id');
    $table->unsignedBigInteger('section_id')->nullable();
    $table->unsignedBigInteger('subject_id');
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
    $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
    $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
    
    $table->unique(['class_id', 'section_id', 'subject_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_section_subject');
    }
};
