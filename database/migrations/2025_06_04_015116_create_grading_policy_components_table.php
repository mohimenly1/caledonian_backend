<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_grading_policy_components_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grading_policy_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_policy_id')->constrained('grading_policies')->onDelete('cascade');
            $table->foreignId('assessment_type_id')->constrained('assessment_types')->onDelete('cascade');
            $table->decimal('weight_percentage', 5, 2); // e.g., 20.00 for 20%
            $table->unsignedInteger('min_items_required')->nullable()
            ->comment('الحد الأدنى لعدد التقييمات من هذا النوع التي يجب تصحيحها');
        
        $table->unsignedInteger('max_items_counted')->nullable()
            ->comment('مثال: احتساب أفضل 2 من أصل 3 اختبارات قصيرة');
        
            $table->boolean('drop_lowest_score')->default(false); // Another option
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['grading_policy_id', 'assessment_type_id'], 'grading_policy_unique');

        });
    }

    public function down()
    {
        Schema::dropIfExists('grading_policy_components');
    }
};