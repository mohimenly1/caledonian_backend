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
            $table->foreign('comment_id')
                  ->references('id')
                  ->on('comments')
                  ->onDelete('cascade');
        });
        
        // Re-add other foreign keys here
    }
    
    public function down()
    {
        Schema::table('comment_likes', function (Blueprint $table) {
            $table->dropForeign(['comment_id']);
        });
        
        // Drop other foreign keys here
    }
};
