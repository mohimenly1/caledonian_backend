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
        Schema::create('issued_salaries_per_hour', function (Blueprint $table) {
            $table->id();
    $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // Link to the employee
    $table->foreignId('deduction_id')->nullable()->constrained('deductions_per_hour')->onDelete('set null'); // Optional link to fixed deduction
    $table->date('issued_date'); // Salary issuance date
    $table->decimal('bonus', 8, 2)->nullable(); // Optional bonus
    $table->enum('currency', ['LYD', 'USD']); // Currency type
    $table->decimal('custom_deduction', 8, 2)->nullable(); // Optional custom deduction
    $table->decimal('net_salary', 10, 2); // Net salary after deductions
    $table->text('note')->nullable(); // Optional notes
    $table->softDeletes();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issued_salaries_per_hour');
    }
};
