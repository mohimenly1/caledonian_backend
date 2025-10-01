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
        Schema::table('teachers_type', function (Blueprint $table) {
            $table->string('role_name')->after('type'); // Adds after 'type' column
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers_type', function (Blueprint $table) {
            $table->dropColumn('role_name'); // Rollback: removes the column
        });
    }
};
