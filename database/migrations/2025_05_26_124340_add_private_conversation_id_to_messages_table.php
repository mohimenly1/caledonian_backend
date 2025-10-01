<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('private_conversation_id')->nullable()->after('chat_group_id');
            $table->foreign('private_conversation_id')
                  ->references('id')
                  ->on('private_conversations')
                  ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['private_conversation_id']);
            $table->dropColumn('private_conversation_id');
        });
    }
};
