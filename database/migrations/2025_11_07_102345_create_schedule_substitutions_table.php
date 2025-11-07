<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_substitutions', function (Blueprint $table) {
            $table->id();

            // الحصة الأصلية التي يتم استبدالها
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->onDelete('cascade');

            // المعلم البديل
            $table->foreignId('substitute_teacher_id')->constrained('users')->onDelete('cascade');

            $table->date('substitution_date'); // تاريخ اليوم الذي سيتم فيه التبديل
            $table->text('reason')->nullable(); // سبب الغياب أو التبديل

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_substitutions');
    }
};
