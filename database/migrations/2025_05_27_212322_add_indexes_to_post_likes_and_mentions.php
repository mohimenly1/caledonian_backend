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
        Schema::table('post_likes', function (Blueprint $table) {
            // Add composite index
            $table->index(['post_id', 'user_id']);
        });

        Schema::table('post_mentions', function (Blueprint $table) {
            // Add composite index
            $table->index(['post_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('post_likes', function (Blueprint $table) {
            $table->dropIndex(['post_id', 'user_id']);
        });

        Schema::table('post_mentions', function (Blueprint $table) {
            $table->dropIndex(['post_id', 'user_id']);
        });
    }
};
