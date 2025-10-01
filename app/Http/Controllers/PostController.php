<?php

// app/Http/Controllers/PostController.php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with([
                // CRITICAL CHANGE HERE: Eager load user's badges
                'user' => function ($query) {
                    $query->with('badges'); // Eager load the 'badges' relationship for the user
                },
          // Correct way to load users who liked the post along with their badges
          'likes' => function ($query) { // 'likes' is the relationship name on the Post model
            $query->with('badges') // Eager load badges for each user in the 'likes' relationship
                  ->select('users.id', 'users.name', 'users.username', 'users.photo'); // Select desired fields
        },
                'mentions' => function($query) {
                    $query->select('users.id', 'users.name', 'users.username', 'users.photo');
                },
                'comments.user' => function ($query) {
                    $query->with('badges'); // Eager load badges for comment authors
                },
                'comments' => function($query) {
                    $query->with(['user' => function($userQuery) { // Example for comment user badges
                        $userQuery->with('badges');
                    }])
                          ->withCount(['likes as likes_count'])
                          ->latest()
                          ->limit(2);
                }
            ])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(10);

        // Manually add is_liked to each post
        // And ensure is_online is present if not automatically appended for all users via $appends
        foreach ($posts as $post) {
            $post->is_liked = $post->likes->contains(auth()->id());
            // If user model's is_online isn't always appended via $appends, you might need to set it.
            // However, your User model has protected $appends = ['is_online']; so it should be there.
        }
    
        return response()->json([
            'success' => true,
            'posts' => $posts
        ]);
    }


// Update the store method in PostController
// Update the store method in PostController
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'content' => 'nullable|string',
        'media' => 'required_without:content|file',
        'parent_id' => 'nullable|exists:posts,id',
        'mentions' => 'nullable|array',
        'mentions.*' => 'exists:users,id'
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $mediaPath = null;
    $mediaType = null;

    if ($request->hasFile('media')) {
        $file = $request->file('media');
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        $filename = 'media_'.uniqid().'.'.$extension;
        $mediaPath = $file->storeAs('posts', $filename, 'public');

        // *** START OF FINAL FIX: Prioritize Extension Check for Audio ***
        // سنقوم بالتحقق من امتداد الملف أولاً للملفات الصوتية لضمان الدقة
        
        if ($extension === 'aac' || $extension === 'm4a' || $extension === 'mp3' || $extension === 'ogg') {
            $mediaType = 'audio';
        }
        // إذا لم يكن ملفاً صوتياً، تحقق من نوع MIME كالمعتاد
        elseif (str_starts_with($mimeType, 'image/')) {
            $mediaType = 'image';
        } 
        elseif (str_starts_with($mimeType, 'video/')) {
            $mediaType = 'video';
        }
        // *** END OF FINAL FIX ***
    }

    $post = Post::create([
        'user_id' => auth()->id(),
        'content' => $request->content,
        'media_path' => $mediaPath,
        'media_type' => $mediaType,
        'parent_id' => $request->parent_id
    ]);

    if ($request->has('mentions')) {
        $post->mentions()->attach($request->mentions);
    }

    $post->load(['user' => function ($query) {
        $query->with('badges');
    }, 'mentions']);

    return response()->json([
        'success' => true,
        'post' => $post
    ]);
}
private function getLikingUsersWithBadges(Post $post)
{
    return $post->likes()->with('badges')->get()->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'photo' => $user->photo,
            'is_online' => $user->is_online, // User model appends this
            'badges' => $user->badges->map(function($badge){ // Make sure badges are formatted as expected by Flutter
                return ['name' => $badge->name, 'icon' => $badge->icon]; // Example
            }),
        ];
    });
}
    public function like(Post $post)
    {
        $post->likes()->syncWithoutDetaching(auth()->id());
        $likingUsers = $this->getLikingUsersWithBadges($post);
        
        return response()->json([
            'success' => true,
            'likes_count' => $post->likes()->count(),
            'likes' => $likingUsers // Return the collection with badges

        ]);
    }
    

    public function unlike(Post $post)
    {
        $post->likes()->detach(auth()->id());
        $likingUsers = $this->getLikingUsersWithBadges($post);

        
        return response()->json([
            'success' => true,
            'likes_count' => $post->likes()->count(),
         'likes' => $likingUsers
        ]);
    }

    public function show(Post $post)
    {
        // Load the post with its relationships, including user badges
        $post->load([
            'user' => function ($query) {
                $query->with('badges');
            },
            'likes' => function ($query) {
                $query->select('users.id', 'users.name', 'users.username', 'users.photo');
            },
            'mentions' => function ($query) {
                $query->select('users.id', 'users.name', 'users.username', 'users.photo');
            },
            'comments.user' => function ($query) { // Also for comment authors if needed
                $query->with('badges');
            },
            'comments' => function ($query) {
                $query->withCount(['likes as likes_count'])->latest();
            }
        ]);
    
        // Manually add is_liked (if not already done during initial load or if show needs it independently)
        // This requires the 'likes' relationship to be loaded, which it is.
        $post->is_liked = $post->likes->contains(auth()->id());
    
        return response()->json([
            'success' => true,
            'post' => $post
        ]);
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($post->media_path) {
            Storage::disk('public')->delete($post->media_path);
        }

        $post->delete();

        return response()->json(['success' => true]);
    }
}