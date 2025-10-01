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
        Schema::create('salary_deductions_absences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salary_id');
            $table->unsignedBigInteger('deduction_id')->nullable();
            $table->unsignedBigInteger('absence_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('salary_id')->references('id')->on('salaries')->onDelete('cascade');
            $table->foreign('deduction_id')->references('id')->on('deductions')->onDelete('set null');
            $table->foreign('absence_id')->references('id')->on('absences')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_deductions_absences');
    }
};
