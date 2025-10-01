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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // The action performed, e.g., "created", "deleted", "updated"
            $table->string('description'); // A description of what was done
            $table->json('old_data')->nullable(); // Store previous data (for updates or deletions)
            $table->json('new_data')->nullable(); // Store new data (for creates or updates)
            $table->timestamp('created_at');
            
            // Foreign key reference
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
