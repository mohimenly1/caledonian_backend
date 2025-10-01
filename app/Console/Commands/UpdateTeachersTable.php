<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class UpdateTeachersTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:update-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new fields to the teachers table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                // Add new fields
                $table->text('bio')->nullable()->after('years_of_experience');
                $table->string('specialization')->nullable()->after('bio');
                $table->string('office_hours')->nullable()->after('specialization');
                $table->string('email')->nullable()->after('office_hours');
                $table->json('social_media_links')->nullable()->after('email');
                $table->text('awards_and_achievements')->nullable()->after('social_media_links');
                $table->text('teaching_philosophy')->nullable()->after('awards_and_achievements');
                $table->json('class_schedule')->nullable()->after('teaching_philosophy');
                $table->json('languages_spoken')->nullable()->after('class_schedule');
                $table->json('availability_for_parent_meetings')->nullable()->after('languages_spoken');
            });

            $this->info('Teachers table updated successfully.');
        } else {
            $this->error('Teachers table does not exist.');
        }
    }
}