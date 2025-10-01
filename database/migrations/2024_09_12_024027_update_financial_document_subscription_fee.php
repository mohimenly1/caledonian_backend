<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('financial_document_subscription_fee', function (Blueprint $table) {
            // If foreign keys already exist, you can skip this. Otherwise, create or modify them:
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
        Schema::table('financial_document_subscription_fee', function (Blueprint $table) {
            //
        });
    }
};
