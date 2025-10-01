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
        Schema::create('financial_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->constrained()->onDelete('cascade');
            $table->foreignId('treasury_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('final_amount', 10, 2); // total - discount
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_document_subscription_fee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_document_id')
                ->constrained()
                ->onDelete('cascade')
                ->name('fk_financial_document');  // Shortened foreign key name
            $table->foreignId('subscription_fee_id')
                ->constrained()
                ->onDelete('cascade')
                ->name('fk_subscription_fee');  // Shortened foreign key name
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_document_subscription_fee');
        Schema::dropIfExists('financial_documents');
    }
};

