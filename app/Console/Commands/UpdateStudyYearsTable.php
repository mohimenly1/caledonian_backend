<?php

// app/Console/Commands/UpdateStudyYearsTable.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpdateStudyYearsTable extends Command
{
    protected $signature = 'table:update-study-years';
    protected $description = 'Update the study_years table structure';

    public function handle()
    {
        // Rename column year_study to name
        if (Schema::hasColumn('study_years', 'year_study')) {
            Schema::table('study_years', function (Blueprint $table) {
                $table->renameColumn('year_study', 'name');
            });
            $this->info('Renamed column year_study to name.');
        }

        // Add start_date, end_date, is_active, and soft deletes
        Schema::table('study_years', function (Blueprint $table) {
            if (!Schema::hasColumn('study_years', 'start_date')) {
                $table->date('start_date')->nullable();
                $this->info('Added start_date column.');
            }

            if (!Schema::hasColumn('study_years', 'end_date')) {
                $table->date('end_date')->nullable();
                $this->info('Added end_date column.');
            }

            if (!Schema::hasColumn('study_years', 'is_active')) {
                $table->boolean('is_active')->default(false);
                $this->info('Added is_active column.');
            }

            if (!Schema::hasColumn('study_years', 'deleted_at')) {
                $table->softDeletes();
                $this->info('Added soft deletes (deleted_at column).');
            }
        });

        $this->info('study_years table successfully updated.');
    }
}
