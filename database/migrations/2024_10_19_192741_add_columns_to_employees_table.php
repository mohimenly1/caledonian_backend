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
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->after('name');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('passport_number')->nullable()->after('date_of_birth');
            $table->json('attached_files')->nullable()->after('photos'); // Can store PDFs or images in JSON format
        });
    }

    /**
     * Reverse the migrations.
     */

     public function down()
     {
         Schema::table('employees', function (Blueprint $table) {
             $table->dropColumn(['gender', 'date_of_birth', 'passport_number', 'attached_files']);
         });
     }
};
