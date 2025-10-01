<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_student_report_comments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_report_comments', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->constrained('terms')->onDelete('cascade'); // Nullable if comment is for the whole year
            $table->foreignId('comment_by_user_id')->constrained('users')->onDelete('restrict'); // User who wrote the comment
            $table->string('commenter_role')->nullable()->comment('Role of commenter, e.g., Homeroom Teacher, Principal');
            $table->string('comment_type')->default('General'); // e.g., "General", "Behavioral", "Academic Progress"
            $table->text('comment_text');
            $table->date('date_of_comment');
            $table->boolean('is_visible_on_report')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_report_comments');
    }
};




/*

student_report_comments (ملاحظات وتعليقات إضافية على الصحيفة)
الوظيفة: لتخزين التعليقات العامة التي قد يكتبها مربي الصف، أو مدير المدرسة، أو مرشد الطلاب على صحيفة الطالب، والتي تكون منفصلة عن ملاحظات المعلمين على المواد الدراسية.
ملف الترحيل (Migration):
*/

