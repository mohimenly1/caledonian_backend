<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_item_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('parent_user_id')->constrained('users');
            $table->string('subject');
            $table->string('status')->default('open'); // open, pending, closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_item_tickets');
    }
};
