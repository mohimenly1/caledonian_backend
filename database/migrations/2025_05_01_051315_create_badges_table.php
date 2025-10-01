<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // Insert default badges
        DB::table('badges')->insert([
            ['name' => 'moder', 'description' => 'Moderator', 'icon' => 'shield'],
            ['name' => 'hr', 'description' => 'Human Resources', 'icon' => 'people'],
            ['name' => 'admin', 'description' => 'Administrator', 'icon' => 'star'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
