<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_report_card_templates_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('report_card_templates', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('school_id')->constrained('schools')->onDelete('cascade'); // معرف المدرسة

            $table->string('name'); // مثل: "قالب المرحلة الثانوية - فصلي", "تقرير رياض الأطفال"

            $table->enum('type', ['report_card', 'transcript', 'progress_report'])->default('report_card');
            // نوع القالب: بطاقة تقرير، كشف درجات، تقرير تقدم

            $table->text('description')->nullable(); // وصف اختياري للقالب

            $table->string('template_view_path')->nullable()->comment('مثل: مسار ملف Blade مثل report_cards.templates.school_a_secondary');
            // مسار العرض المستخدم لعرض القالب

            $table->longText('header_content')->nullable()->comment('محتوى HTML أو نص مخصص للرأس');
            $table->longText('footer_content')->nullable()->comment('محتوى HTML أو نص مخصص للتذييل');

            $table->json('layout_options')->nullable()->comment('بيانات JSON لتخزين تفضيلات التنسيق، مثل الأقسام والترتيب وما إلى ذلك');
            // خيارات التنسيق الخاصة بالقالب

            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->onDelete('set null')->comment('إذا كان القالب مخصصًا لمستوى دراسي معين');
            // ربط اختياري بمستوى دراسي محدد

            $table->boolean('is_default')->default(false)->comment('هل هذا القالب هو القالب الافتراضي لهذا النوع والمدرسة/الصف؟');
            $table->boolean('is_active')->default(true); // هل القالب مفعل؟

            $table->timestamps(); // تاريخ الإنشاء والتحديث
            $table->softDeletes(); // الحذف الناعم
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_card_templates');
    }
};


/*

بالتأكيد، سأقوم الآن بإنشاء ملفات الترحيل (Migrations) والنماذج (Models) مع العلاقات للجوانب الأربعة التي طلبتها، بناءً على الهيكل السابق الذي عملنا عليه.

سنعمل على:

report_card_templates (قوالب الصحائف)
generated_report_cards (سجل الصحائف المُصدرة)
student_report_comments (ملاحظات وتعليقات إضافية على الصحيفة)
نظام تقييم السلوك والجوانب غير الأكاديمية (سنبدأ بهيكل أساسي)
1. report_card_templates (قوالب الصحائف والشهادات)
الوظيفة: يسمح هذا الجدول للمدارس بتعريف قوالب مختلفة للشهادات وكشوف الدرجات (الصحائف). قد تحتاج مدرسة قوالب مختلفة للمراحل الدراسية المختلفة (ابتدائي، متوسط، ثانوي) أو لأنواع مختلفة من التقارير (فصلي، سنوي، تقرير تقدم). هذا الجدول سيخزن معلومات حول هذه القوالب، مثل اسمها، نوعها، وربما مسار لملف القالب الفعلي أو تعريفات لهيكله.

*/