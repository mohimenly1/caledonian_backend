<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_evaluation_areas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('evaluation_areas', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., "السلوك والانضباط", "المهارات الحياتية"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_areas');
    }
};


/*

evaluation_areas (مجالات التقييم غير الأكاديمي)
الوظيفة: لتصنيف مجالات التقييم المختلفة مثل "السلوك"، "المهارات الاجتماعية"، "المواظبة".
*/