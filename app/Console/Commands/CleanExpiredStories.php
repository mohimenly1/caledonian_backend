<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use Illuminate\Support\Facades\Storage;

class CleanExpiredStories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:clean-expired-stories';
    protected $signature = 'stories:clean';
    protected $description = 'Clean up expired stories and their media';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredStories = Story::expired()->get();
        
        foreach ($expiredStories as $story) {
            Storage::disk('public')->delete($story->media_path);
            $story->delete();
        }
        
        $this->info('Cleaned up ' . $expiredStories->count() . ' expired stories');
    }
}
