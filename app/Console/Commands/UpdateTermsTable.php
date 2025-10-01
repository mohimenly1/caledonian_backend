<?php

// app/Console/Commands/UpdateTermsTable.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpdateTermsTable extends Command
{
    protected $signature = 'table:update-terms';
    protected $description = 'Add is_active column to the terms table';

    public function handle()
    {
        if (!Schema::hasColumn('terms', 'is_active')) {
            Schema::table('terms', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('end_date');
            });

            $this->info('✅ is_active column added to terms table.');
        } else {
            $this->warn('⚠️ is_active column already exists in terms table.');
        }
    }
}
