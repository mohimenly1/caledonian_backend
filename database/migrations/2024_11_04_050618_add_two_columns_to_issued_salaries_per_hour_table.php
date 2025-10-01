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
        Schema::table('issued_salaries_per_hour', function (Blueprint $table) {
            $table->decimal('base_salary', 10, 2); // Base salary before deductions
            $table->string('delay_message'); // Textual representation of delay
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issued_salaries_per_hour', function (Blueprint $table) {
            //
        });
    }
};
