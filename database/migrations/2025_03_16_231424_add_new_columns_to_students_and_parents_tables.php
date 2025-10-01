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
        Schema::table('students', function (Blueprint $table) {
            $table->string('note')->nullable()->after('study_year_id');
            $table->string('subscriptions')->nullable()->after('note');
            $table->string('missing')->nullable()->after('subscriptions');
        });
    
        Schema::table('parents', function (Blueprint $table) {
            $table->string('note')->nullable()->after('pin_code');
            $table->boolean('discount')->default(false)->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['note', 'subscriptions', 'missing']);
        });
    
        Schema::table('parents', function (Blueprint $table) {
            $table->dropColumn(['note', 'discount']);
        });
    }
};
