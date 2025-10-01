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
        Schema::table('users', function (Blueprint $table) {
              // Add 'bio' column, can be lengthy text, nullable
              $table->text('bio')->nullable()->after('address');

              // Add 'cover_photo' column for storing the path to the image, nullable
              $table->string('cover_photo')->nullable()->after('photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
                  // Define how to reverse the changes: drop the columns
                  $table->dropColumn(['bio', 'cover_photo']);
        });
    }
};
