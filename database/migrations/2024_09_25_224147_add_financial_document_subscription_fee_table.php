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
        Schema::create('financial_document_subscription_fee', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_document_id');
            $table->unsignedBigInteger('subscription_fee_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        
            // Foreign key constraints with shorter names
            $table->foreign('financial_document_id', 'fd_subscription_fee_doc_fk')
                  ->references('id')->on('financial_documents')->onDelete('cascade');
            $table->foreign('subscription_fee_id', 'fd_subscription_fee_fk')
                  ->references('id')->on('subscription_fees')->onDelete('cascade');
            $table->foreign('student_id', 'fd_subscription_fee_student_fk')
                  ->references('id')->on('students')->onDelete('cascade');
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
