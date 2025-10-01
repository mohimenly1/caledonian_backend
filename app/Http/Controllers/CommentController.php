<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required_without:media',
            'media' => 'required_without:content|file|mimes:jpg,jpeg,png,mp4,mov,mp3,wav',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $mediaPath = null;
        $mediaType = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $extension = $file->getClientOriginalExtension();
            
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $mediaType = 'image';
            } elseif (in_array($extension, ['mp4', 'mov'])) {
                $mediaType = 'video';
            } elseif (in_array($extension, ['mp3', 'wav'])) {
                $mediaType = 'audio';
            }

            $mediaPath = $file->store('comments', 'public');
        }

        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'media_path' => $mediaPath,
            'media_type' => $mediaType
        ]);

        return response()->json([
            'success' => true,
            'comment' => $comment->load('user')
        ]);
    }


    public function indexForPost(Post $post) { // Assuming you have such a method
        $comments = $post->comments()->with(['user' => function($query) {
            $query->with('badges'); // Eager load badges
        }])->latest()->paginate(15); // Or your pagination logic
    
        // Ensure is_online is included if not an $appends on User model visible here
        // (Your User model already appends 'is_online', so it should be fine)
    
        return response()->json(['success' => true, 'comments' => $comments]);
    }
    public function update(Request $request, Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required_without:media',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,mp3,wav',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $mediaPath = $comment->media_path;
        $mediaType = $comment->media_type;

        if ($request->hasFile('media')) {
            // Delete old media if exists
            if ($mediaPath) {
                Storage::disk('public')->delete($mediaPath);
            }

            $file = $request->file('media');
            $extension = $file->getClientOriginalExtension();
            
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $mediaType = 'image';
            } elseif (in_array($extension, ['mp4', 'mov'])) {
                $mediaType = 'video';
            } elseif (in_array($extension, ['mp3', 'wav'])) {
                $mediaType = 'audio';
            }

            $mediaPath = $file->store('comments', 'public');
        }

        $comment->update([
            'content' => $request->content ?? $comment->content,
            'media_path' => $mediaPath,
            'media_type' => $mediaType
        ]);

        return response()->json([
            'success' => true,
            'comment' => $comment->load('user')
        ]);
    }

    public function destroy(Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($comment->media_path) {
            Storage::disk('public')->delete($comment->media_path);
        }

        $comment->delete();

        return response()->json(['success' => true]);
    }
}