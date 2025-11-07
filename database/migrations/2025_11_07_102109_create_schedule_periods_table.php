<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // مثال: "الحصة الأولى", "الفسحة"
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false); // لتحديد إذا كانت الفترة فسحة أم حصة دراسية

            // مفتاح أجنبي اختياري لربط الفترة بمرحلة دراسية معينة
            // يسمح بوجود توقيتات مختلفة للروضة والابتدائي مثلاً
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_periods');
    }
};
