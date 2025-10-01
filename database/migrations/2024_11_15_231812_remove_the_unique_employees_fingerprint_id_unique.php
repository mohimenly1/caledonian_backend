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
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_fingerprint_id_unique'); // Remove the unique constraint
            $table->string('fingerprint_id')->nullable()->change(); // Update the column to be nullable without unique
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('fingerprint_id')->unique()->nullable(false)->change(); // Revert changes (add unique back and make non-nullable)
        });
    }
};
