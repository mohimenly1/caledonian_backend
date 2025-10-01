<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpdateClassesTable extends Command
{
    protected $signature = 'table:update-classes';
    protected $description = 'Update the classes table to add grade_level_id, study_year_id, and is_active columns';

    public function handle()
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'grade_level_id')) {
                $table->foreignId('grade_level_id')
                      ->nullable()
                      ->constrained('grade_levels')
                      ->onDelete('cascade');
                $this->info('✅ Added grade_level_id column.');
            }

            if (!Schema::hasColumn('classes', 'study_year_id')) {
                $table->foreignId('study_year_id')
                      ->nullable()
                      ->constrained('study_years')
                      ->onDelete('cascade');
                $this->info('✅ Added study_year_id column.');
            }

            if (!Schema::hasColumn('classes', 'is_active')) {
                $table->boolean('is_active')->default(true);
                $this->info('✅ Added is_active column.');
            }
        });

        $this->info('🎉 classes table has been successfully updated.');
    }
}
