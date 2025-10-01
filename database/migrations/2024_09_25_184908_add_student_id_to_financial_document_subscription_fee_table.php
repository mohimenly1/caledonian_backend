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
        Schema::table('financial_document_subscription_fee', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->after('subscription_fee_id');

            // Optionally, add a foreign key constraint for student_id
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_document_subscription_fee', function (Blueprint $table) {
            //
        });
    }
};
