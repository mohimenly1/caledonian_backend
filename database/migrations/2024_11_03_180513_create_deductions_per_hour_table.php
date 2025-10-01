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
        Schema::create('deductions_per_hour', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 8, 2); // Fixed deduction amount
            $table->string('description')->nullable(); // Description or reason for the deduction
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deductions_per_hour');
    }
};
