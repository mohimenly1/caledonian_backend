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
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->integer('age');
            $table->string('school');
            $table->string('address');
            $table->string('zip');
            $table->string('parent_phone');
            $table->string('parent_email');
            $table->string('first_guardian_name');
            $table->string('first_guardian_phone');
            $table->string('second_guardian_name')->nullable();
            $table->string('second_guardian_phone')->nullable();
            $table->string('place_of_work')->nullable();
            $table->string('emergency_contact');
            $table->string('pickup_person_1_name')->nullable();
            $table->string('pickup_person_1_phone')->nullable();
            $table->string('pickup_person_2_name')->nullable();
            $table->string('pickup_person_2_phone')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
