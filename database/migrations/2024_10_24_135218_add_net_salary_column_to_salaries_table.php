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
        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('net_salary', 10, 2)->nullable(); // Adding net_salary column
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn('net_salary'); // Rollback for net_salary column
        });
    }
};
