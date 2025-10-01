<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_arrivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents'); // ولي الأمر الذي وصل
            $table->foreignId('scanned_by_user_id')->constrained('users'); // المشرف الذي قام بالمسح
            $table->text('message'); // رسالة الاشعار التي سيتم انشاؤها
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_arrivals');
    }
};