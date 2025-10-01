<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_generated_report_cards_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('generated_report_cards', function (Blueprint $table) {
            $table->id();

      

            $table->foreignId('student_id')->constrained('students')->onDelete('cascade'); 
            // معرف الطالب

            $table->foreignId('report_card_template_id')->nullable()->constrained('report_card_templates')->onDelete('set null'); 
            // القالب المستخدم في توليد التقرير (اختياري)

            $table->enum('report_type', ['report_card', 'transcript', 'progress_report'])->comment('يتطابق مع نوع القالب أو النوع العام');
            // نوع التقرير: بطاقة تقرير، كشف درجات، تقرير تقدم

            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade'); 
            // السنة الدراسية

            $table->foreignId('term_id')->nullable()->constrained('terms')->onDelete('cascade'); 
            // الفترة الدراسية (اختياري للتقارير السنوية)

            $table->dateTime('generation_date'); 
            // تاريخ توليد التقرير

            $table->foreignId('generated_by_user_id')->constrained('users')->onDelete('restrict'); 
            // المستخدم الذي قام بتوليد التقرير

            $table->string('file_path')->nullable()->comment('المسار إلى ملف PDF أو أي تنسيق آخر'); 
            // مسار الملف الناتج

            $table->longText('data_snapshot_json')->nullable()->comment('لقطة بيانات بصيغة JSON للحفاظ على دقة التقرير تاريخيًا'); 
            // نسخة من البيانات وقت التوليد (للتوثيق)

            $table->unsignedInteger('version')->default(1); 
            // رقم إصدار التقرير (في حال تم تجديده)

            $table->enum('status', ['Generated', 'Issued', 'Archived'])->default('Generated'); 
            // حالة التقرير: تم توليده، تم إصداره، مؤرشف

            $table->text('notes')->nullable(); 
            // ملاحظات إضافية

            $table->timestamps(); 
            // تاريخ الإنشاء والتحديث

            // لا يوجد softDeletes لأن هذه عادةً جداول تسجيل/أرشفة، لكن يمكن إضافتها إذا لزم الأمر.
        });
    }

    public function down()
    {
        Schema::dropIfExists('generated_report_cards');
    }
};




/*

 generated_report_cards (سجل الصحائف المُصدرة)
الوظيفة: يسجل هذا الجدول كل مرة يتم فيها إنشاء صحيفة درجات أو شهادة طالب بشكل رسمي. هذا مفيد للتدقيق، ولإعادة طباعة نسخة طبق الأصل، والرجوع التاريخي للبيانات التي تم إصدارها.
*/