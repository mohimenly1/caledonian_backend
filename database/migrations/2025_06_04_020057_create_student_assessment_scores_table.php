<?php



/*


17. student_assessment_scores (درجات الطلاب في التقييمات الفردية)
الوظيفة: هذا الجدول هو البديل الرئيسي لجدول grades الحالي لديك (فيما يتعلق بالدرجات الفردية). سيخزن هذا الجدول الدرجة التي حصل عليها كل طالب في كل تقييم (assessment) محدد. هنا يتم رصد الدرجات الفعلية.
ملف الترحيل (Migration):


*/

// database/migrations/xxxx_xx_xx_xxxxxx_create_student_assessment_scores_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('score_obtained', 8, 2)->nullable(); // Nullable initially until graded
            $table->dateTime('submission_timestamp')->nullable()->comment('When the student submitted their work');
            $table->dateTime('grading_timestamp')->nullable()->comment('When the teacher graded this submission');
            $table->foreignId('graded_by_teacher_id')->nullable()->constrained('users')->onDelete('set null'); // Teacher who graded
            $table->text('teacher_remarks')->nullable()->comment('Teacher feedback on this specific submission');
            $table->text('student_remarks')->nullable()->comment('Student comments, if allowed');
            $table->string('submission_content_path')->nullable()->comment('Path to submitted file, if any');
            $table->enum('status', ['Pending Submission', 'Submitted', 'Late Submission', 'Graded', 'Missing', 'Resubmission Requested', 'Excused'])
                  ->default('Pending Submission');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assessment_id', 'student_id']); // Each student has one score entry per assessment
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_assessment_scores');
    }
};