<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\ChatGroup;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller
{

    public function index()
    {
       
        $user = auth()->user();
        
        // Get active stories grouped by user_id with the latest first
        $stories = Story::with(['user', 'views'])
            ->active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('user_id');
        
        // Transform the grouped collection
        $formattedStories = $stories->map(function ($userStories) {
            return [
                'user' => $userStories->first()->user,
                'latest_story' => $userStories->first(), // The most recent story
                'stories' => $userStories // All stories for this user
            ];
        })->values();
        
        return response()->json([
            'stories' => $formattedStories,
            'viewed_stories' => $user->storyViews()->pluck('story_id')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov|max:10240', // 10MB max
    //         'caption' => 'nullable|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $file = $request->file('media');
    //     $mediaType = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
    //     $path = $file->store('stories', 'public');

    //     $story = Story::create([
    //         'user_id' => auth()->id(),
    //         'media_path' => $path,
    //         'media_type' => $mediaType,
    //         'caption' => $request->caption,
    //         'expires_at' => now()->addHours(24),
    //     ]);

    //     return response()->json($story->load('user'), 201);
    // }


    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov|max:10240', // 10MB max
        'caption' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $file = $request->file('media');
    $mediaType = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
    $extension = $file->getClientOriginalExtension();
    
    // If it's a video and in MOV format, convert to MP4
    if ($mediaType === 'video' && strtolower($extension) === 'mov') {
        $path = $this->convertMovToMp4($file);
    } else {
        // For images and already MP4 videos, store as is
        $path = $file->store('stories', 'public');
    }

    $story = Story::create([
        'user_id' => auth()->id(),
        'media_path' => $path,
        'media_type' => $mediaType,
        'caption' => $request->caption,
        'expires_at' => now()->addHours(24),
    ]);

    return response()->json($story->load('user'), 201);
}

protected function convertMovToMp4($file)
{
    // Store the original MOV file temporarily
    $tempPath = $file->storeAs('temp', uniqid().'.mov', 'public');
    $tempFullPath = storage_path('app/public/'.$tempPath);
    
    // Set up FFmpeg
    $ffmpeg = FFMpeg::create([
        'ffmpeg.binaries'  => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
        'ffprobe.binaries' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
    ]);
    
    // Open the MOV file
    $video = $ffmpeg->open($tempFullPath);
    
    // Create output path
    $outputFilename = uniqid().'.mp4';
    $outputPath = 'stories/'.$outputFilename;
    $outputFullPath = storage_path('app/public/'.$outputPath);
    
    // Configure format
    $format = new X264();
    $format->setAudioCodec('aac');
    
    // Convert and save
    $video->save($format, $outputFullPath);
    
    // Delete the temporary MOV file
    unlink($tempFullPath);
    
    return $outputPath;
}
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }


    public function view(Story $story)
    {
        if ($story->expires_at <= now()) {
            return response()->json(['message' => 'Story has expired'], 410);
        }

        // Check if already viewed
        if (!$story->views()->where('user_id', auth()->id())->exists()) {
            StoryView::create([
                'story_id' => $story->id,
                'user_id' => auth()->id(),
                'viewed_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Story viewed']);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Story $story)
    {
        if ($story->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($story->media_path);
        $story->delete();

        return response()->json(['message' => 'Story deleted']);
    }

    public function myStories()
    {
        $stories = auth()->user()
            ->stories()
            ->with('views.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($stories);
    }
}
