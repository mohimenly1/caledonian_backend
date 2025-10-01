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
        Schema::table('financial_documents', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign('financial_documents_student_id_foreign'); // Use the foreign key name directly

            // Now drop the student_id column
            $table->dropColumn('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_documents', function (Blueprint $table) {
            // Add back the student_id column
            $table->unsignedBigInteger('student_id')->index();

            // Restore the foreign key constraint
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }
};
