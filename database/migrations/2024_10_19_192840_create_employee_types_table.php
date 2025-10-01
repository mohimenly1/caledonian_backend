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
        Schema::create('employee_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Add the foreign key to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_type_id')->nullable()->after('teacher_type_id');
            $table->foreign('employee_type_id')->references('id')->on('employee_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['employee_type_id']);
            $table->dropColumn('employee_type_id');
        });

        Schema::dropIfExists('employee_types');
    }
};
