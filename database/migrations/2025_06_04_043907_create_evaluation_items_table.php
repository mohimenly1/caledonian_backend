<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_evaluation_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('evaluation_items', function (Blueprint $table) {
            $table->id();

            // معرف مجال التقييم المرتبط بهذا البند
            // يُفترض أن يكون كل بند تقييم تابعًا لمجال تقييم معين في مدرسة ما
            $table->foreignId('evaluation_area_id')->constrained('evaluation_areas')->onDelete('cascade');

            $table->string('name'); 
            // اسم البند (مثال: "الالتزام بالوقت", "التعاون", "المبادرة")

            $table->text('description')->nullable(); 
            // وصف إضافي للبند (اختياري)

            // نوع التقييم المستخدم لهذا البند:
            // - Scale: مقياس محدد مسبقًا (مثل ممتاز، جيد)
            // - CommentOnly: مجرد تعليق
            // - Checkbox: نعم/لا
            // - NumericValue: قيمة رقمية (مثلاً من 1 إلى 10)
            $table->enum('grading_type', ['Scale', 'CommentOnly', 'Checkbox', 'NumericValue'])->default('Scale');

            // يمكن ربط البند بمقياس تقييم محدد مستقبلاً إذا تم استخدام جدول للمقاييس
            // $table->foreignId('grading_scale_id')->nullable()->constrained('grading_scales')->onDelete('set null');

            $table->boolean('is_active')->default(true); 
            // حالة تفعيل البند

            $table->integer('sort_order')->default(0); 
            // ترتيب عرض البند داخل نفس مجال التقييم

            $table->timestamps(); 
            // تاريخ الإنشاء والتحديث

            $table->softDeletes(); 
            // الحذف الناعم (لإخفاء البند دون حذفه فعليًا)

            // لا يمكن تكرار نفس اسم البند داخل نفس مجال التقييم
            $table->unique(['evaluation_area_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_items');
    }
};


/*


4b. evaluation_items (بنود التقييم غير الأكاديمي)
الوظيفة: لتحديد البنود أو السمات المحددة التي سيتم تقييمها ضمن كل مجال.


*/