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
        Schema::create('health_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->integer('age');
            $table->float('weight');
            $table->float('height');
            $table->string('blood_type', 5);
            $table->text('medical_history')->nullable();
            $table->string('hearing')->nullable();
            $table->string('sight')->nullable();
            $table->boolean('diabetes_mellitus')->default(false);
            $table->text('food_allergies')->nullable();
            $table->text('chronic_disease')->nullable();
            $table->text('clinical_examination')->nullable();
            $table->string('result_clinical_examination')->nullable();
            $table->text('vaccinations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_files');
    }
};
