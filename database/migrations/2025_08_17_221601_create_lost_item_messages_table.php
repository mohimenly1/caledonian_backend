<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_item_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('lost_item_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Sender (parent or admin)
            $table->text('message');
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_item_messages');
    }
};
