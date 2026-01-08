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
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->nullable()->after('system_message_type');
            $table->string('assignment_type')->nullable()->after('assignment_id'); // 'assignment' or 'note'
            $table->unsignedBigInteger('teacher_external_id')->nullable()->after('assignment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['assignment_id', 'assignment_type', 'teacher_external_id']);
        });
    }
};
