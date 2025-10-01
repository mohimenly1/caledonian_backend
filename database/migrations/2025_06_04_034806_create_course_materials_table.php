<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_course_materials_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_offering_id')->constrained('course_offerings')->onDelete('cascade');

            // قد تكون المادة عامة للمقرر الدراسي أو مرتبطة بفصل دراسي معين
            $table->foreignId('term_id')->nullable()->constrained('terms')->onDelete('cascade');

            $table->foreignId('uploader_teacher_id')->constrained('users')->onDelete('cascade'); // معرّف المستخدم الخاص بالمعلم الذي قام بالرفع

            $table->string('title'); // عنوان المادة
            $table->text('description')->nullable(); // وصف للمادة (اختياري)

            $table->enum('material_type', ['File', 'Link', 'VideoEmbed', 'TextContent']);
            // نوع المادة: ملف، رابط، فيديو مضمّن، أو محتوى نصي

            $table->text('content_path_or_url_or_text');
            // يخزن مسار الملف أو الرابط أو النص/HTML المضمّن

            $table->dateTime('publish_date')->useCurrent(); // تاريخ النشر، بشكل افتراضي تاريخ الإدراج
            $table->boolean('is_visible_to_students')->default(true); // هل المادة مرئية للطلاب؟

            $table->timestamps();
            $table->softDeletes(); // الحذف الناعم
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_materials');
    }
};
