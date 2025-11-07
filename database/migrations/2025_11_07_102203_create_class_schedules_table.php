<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();

            // هذا هو الرابط الأهم! يحدد المعلم والمادة والصف/الشعبة دفعة واحدة
            $table->foreignId('teacher_course_assignment_id')->constrained('teacher_course_assignments')->onDelete('cascade');

            // رابط مع فترة الحصة (متى تبدأ ومتى تنتهي)
            $table->foreignId('schedule_period_id')->constrained('schedule_periods')->onDelete('cascade');

            // رابط مع الفصل الدراسي (Term)
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');

            $table->enum('day_of_week', ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']);

            $table->string('room_number')->nullable(); // رقم القاعة أو المعمل (اختياري)

            $table->timestamps();
            $table->softDeletes();

            // قيد لمنع إضافة حصتين لنفس الشعبة في نفس اليوم ونفس الفترة الزمنية
            // يتم استنتاج الشعبة من خلال teacher_course_assignment_id -> course_offering_id -> section_id
            // سنضيف هذا القيد في الكود لسهولة التعامل معه بدلاً من قاعدة البيانات مباشرة
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
