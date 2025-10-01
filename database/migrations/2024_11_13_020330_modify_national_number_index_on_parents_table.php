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
        Schema::table('parents', function (Blueprint $table) {
                    // Drop the existing unique index (if it exists)
        $table->dropUnique('parents_national_number_unique'); 

        // Add a new unique index for non-null values only
        $table->unique('national_number', 'parents_national_number_unique')->whereNotNull('national_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropUnique('parents_national_number_unique');
        });
    }
};
