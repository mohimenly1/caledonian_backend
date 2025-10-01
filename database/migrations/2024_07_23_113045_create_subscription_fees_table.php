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
        Schema::create('subscription_fees', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // For example, 'Registration', 'Books', etc.
            $table->string('sub_category')->nullable(); // For example, 'New Families', 'Printed Books', etc.
            $table->decimal('amount', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_fees');
    }
};
