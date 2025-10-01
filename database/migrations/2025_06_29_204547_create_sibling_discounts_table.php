<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sibling_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade');
            $table->unsignedInteger('number_of_siblings');
            $table->decimal('discount_percentage', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sibling_discounts');
    }
};
