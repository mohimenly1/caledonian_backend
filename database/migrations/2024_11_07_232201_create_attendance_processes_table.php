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
        Schema::create('attendance_processes', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('absence_id')->nullable();
            $table->enum('entry_by', ['excel', 'manually']);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('absence_id')->references('id')->on('absences')->onDelete('set null');

            // Unique constraint to prevent duplicate records for the same day and employee
            $table->unique(['day', 'employee_id']);
            $table->softDeletes(); // Adds the deleted_at column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_processes');
    }
};
