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
        Schema::create('permissions_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade'); // Refers to employees table
            $table->string('permission_type'); // نوع الإذن
            $table->date('request_date'); // تاريخ تقديم الطلب
            $table->enum('approval_status', ['approved', 'rejected', 'under_review']) // حالة الموافقة
                  ->default('under_review');
            $table->date('start_date')->nullable(); // تاريخ بدء الإذن إن وجد
            $table->date('end_date')->nullable(); // تاريخ انتهاء الإذن إن وجد
            $table->text('reason')->nullable(); // سبب الإذن
            $table->text('remarks')->nullable(); // ملاحظات إضافية
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // اسم أو معرف الشخص المعتمد
            $table->string('file_attachment')->nullable(); // ملف مرفق لدعم الطلب
            $table->timestamp('notified_at')->nullable(); // متى تم إعلام الموظف بالحالة
            $table->date('decision_date')->nullable(); // متى تم اتخاذ قرار
            $table->softDeletes(); // Add soft deletes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions_employees');
    }
};
