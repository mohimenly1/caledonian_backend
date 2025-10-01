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
        Schema::table('comment_likes', function (Blueprint $table) {
            $table->dropForeign(['comment_id']);
        });
        
        // Add other tables that might have foreign keys to comments.id
        // For example, if you have a comment_replies table:
        // $table->dropForeign(['parent_comment_id']);
    }
    
    public function down()
    {
        Schema::table('comment_likes', function (Blueprint $table) {
            $table->foreign('comment_id')
                  ->references('id')
                  ->on('comments')
                  ->onDelete('cascade');
        });
        
        // Re-add other foreign keys here
    }
};
