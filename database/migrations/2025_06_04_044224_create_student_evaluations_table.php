<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_student_evaluations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_evaluations', function (Blueprint $table) {
            $table->id();

            // ربط التقييم بطالب محدد
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            // السنة الدراسية التي ينتمي إليها التقييم
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade');

            // الفصل الدراسي الذي أُجري فيه التقييم
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');

            // بند التقييم (مثال: الالتزام بالوقت، التعاون...)
            $table->foreignId('evaluation_item_id')->constrained('evaluation_items')->onDelete('cascade');

            // المعلّم أو الموظف الذي قام بالتقييم
            $table->foreignId('evaluated_by_user_id')->constrained('users')->onDelete('restrict');

            // تاريخ إجراء التقييم
            $table->date('evaluation_date');

            // القيمة الفعلية للتقييم (يمكن أن تكون نص مثل "ممتاز"، أو رقم، أو "نعم/لا")
            $table->string('evaluation_value')->nullable();

            // تعليق إضافي اختياري حول التقييم
            $table->text('comment_text')->nullable();

            // توقيتات الإنشاء والتحديث
            $table->timestamps();

            // حذف ناعم للتقييم (بدلاً من حذفه نهائيًا)
            $table->softDeletes();

            // لا يمكن تكرار نفس البند لتقييم نفس الطالب في نفس الفصل
            $table->unique(['student_id', 'term_id', 'evaluation_item_id'], 'student_term_item_evaluation_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_evaluations');
    }
};



/*

4c. student_evaluations (تقييمات الطلاب غير الأكاديمية)
الوظيفة: يخزن التقييم الفعلي للطالب على بند معين من بنود التقييم غير الأكاديمي.


| الطالب | البند           | التقييم | الفصل  | المقيّم        | التاريخ    |
| ------ | --------------- | ------- | ------ | -------------- | ---------- |
| أحمد   | الالتزام بالوقت | ممتاز   | الأول  | الأستاذة فاطمة | 2025-06-01 |
| سارة   | التعاون         | نعم     | الثاني | الأستاذ محمود  | 2025-06-02 |


*/