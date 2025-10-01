<?php


// database/migrations/xxxx_xx_xx_xxxxxx_create_student_attendance_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('school_id')->constrained('schools')->onDelete('cascade'); // في حال وجود أكثر من مدرسة
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            // الربط مع الحصة المجدولة في الجدول الدراسي
            // هذا يعني أن الحضور يتم تسجيله لكل حصة بشكل منفصل
            $table->foreignId('timetable_id')->constrained('timetables')->onDelete('cascade');

            // التاريخ المحدد لهذا السجل، مهم للتمييز بين الحصص المتكررة في الأيام المختلفة
            $table->date('attendance_date');

            // الفصل الدراسي الذي يقع فيه هذا السجل، مفيد للتقارير والسياق العام
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');

            $table->enum('status', ['Present', 'Absent', 'Late', 'Excused_Absent', 'Early_Departure']); // الحالة: حاضر، غائب، متأخر، غياب بعذر، مغادرة مبكرة
            $table->text('remarks')->nullable(); // ملاحظات إضافية إن وجدت
            $table->foreignId('recorded_by_user_id')->constrained('users')->onDelete('cascade'); // المستخدم الذي سجل الحضور (معلم/موظف)

            $table->timestamps();
            $table->softDeletes();

            // لا يمكن للطالب أن يمتلك أكثر من سجل حضور لنفس الحصة في نفس التاريخ
            $table->unique(['student_id', 'timetable_id', 'attendance_date'], 'student_period_attendance_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_attendance');
    }
};
