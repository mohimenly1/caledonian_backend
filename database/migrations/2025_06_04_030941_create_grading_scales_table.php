<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            // قد تكون جداول الدرجات مخصصة لسنة دراسية معينة داخل المدرسة
            $table->foreignId('study_year_id')->nullable()->constrained('study_years')->onDelete('cascade');
        
            // اختياريًا، يمكن ربط جدول الدرجات بسياسة تقييم معينة
            $table->foreignId('grading_policy_id')->nullable()->constrained('grading_policies')->onDelete('cascade');
        
            $table->string('grade_label'); // مثل: "A+", "ممتاز", "ناجح"
            $table->decimal('min_percentage', 5, 2); // الحد الأدنى للنسبة المئوية لهذه الدرجة (شامل)
            $table->decimal('max_percentage', 5, 2); // الحد الأقصى للنسبة المئوية لهذه الدرجة (شامل)
            $table->decimal('gpa_point', 4, 2)->nullable()->comment('ما يعادلها كنقطة GPA');
            $table->text('description')->nullable()->comment('ملاحظات أو معنى الدرجة');
            $table->integer('rank_order')->default(0)->comment('لترتيب الدرجات، مثل A+ أعلى من A');
            $table->timestamps();
            $table->softDeletes();
        
            // يجب أن يكون اسم الدرجة فريدًا ضمن المدرسة وسنة الدراسة المعينة
            $table->unique(['study_year_id', 'grade_label'], 'school_year_grade_label_unique');
        
            // لا يجب أن تتداخل النسب المئوية لنفس المقياس (school_id، study_year_id)
            // هذا يتطلب منطق تحقق أكثر تعقيدًا في التطبيق بدلاً من استخدام قيد فريد بسيط في قاعدة البيانات.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
    }
};
