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
        Schema::create('salaries_per_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('hourly_rate', 8, 2); // Hourly pay rate
            $table->integer('mandatory_attendance_time'); // Required attendance time in minutes
            $table->integer('num_classes'); // Number of classes taught
            $table->decimal('class_rate', 8, 2)->nullable(); // Optional rate per class
            $table->softDeletes(); // Adds the deleted_at column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries_per_hours');
    }
};
