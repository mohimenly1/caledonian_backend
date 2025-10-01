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
            $table->string('receipt_number')->unique(); // New column for the receipt number
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('final_amount', 10, 2); // total - discount
            $table->decimal('value_received', 10, 2); // New field for value received
            $table->text('description')->nullable();
            $table->string('payment_method'); // Cash, Instrument, Card
            $table->string('bank_name')->nullable(); // Nullable fields for financial instrument payments
            $table->string('branch_name')->nullable();
            $table->string('account_number')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_documents', function (Blueprint $table) {
            //
        });
    }
};
