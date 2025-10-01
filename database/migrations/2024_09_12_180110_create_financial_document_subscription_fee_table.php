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
            $table->unsignedBigInteger('financial_document_id'); // Define the column first
            $table->unsignedBigInteger('subscription_fee_id');   // Define the column first
            $table->decimal('amount', 10, 2);
            $table->timestamps();
    
            // Add foreign key constraints after defining the columns
            $table->foreign('financial_document_id', 'fk_financial_document')
                  ->references('id')->on('financial_documents')
                  ->onDelete('cascade');
    
            $table->foreign('subscription_fee_id', 'fk_subscription_fee')
                  ->references('id')->on('subscription_fees')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_document_subscription_fee');
    }
};
