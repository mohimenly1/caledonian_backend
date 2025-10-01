<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post; // For fetching user's posts

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ProfileUserController extends Controller
{
        // Get current authenticated user's profile
        public function me(Request $request)
        {
            $user = $request->user();
            // This should now work:
            $user->loadCount(['posts', 'followers', 'following']);
            $user->load('badges');
            return response()->json(['success' => true, 'user' => $user]);
        }
    
        // Show a specific user's profile
        // User $user will be resolved by Route Model Binding (typically by ID)
        // If you want to use username, you'll need to adjust route key name or query manually.
        public function show(User $user)
        {
            // This should now work:
            $user->loadCount(['posts', 'followers', 'following']);
            $user->load('badges');
            return response()->json(['success' => true, 'user' => $user]);
        }
    
        // Fetch posts for a specific user (can be part of 'show' or separate)
        public function posts(User $user, Request $request)
        {
            Log::info("Fetching posts for User ID: {$user->id}, Page: {$request->input('page', 1)}");
    
            try {
                $posts = Post::where('user_id', $user->id)
                             ->with([
                                 'user' => function ($query) { $query->with('badges'); },
                                 'likes' => function($query) {
                                     $query->with('badges')->select('users.id', 'users.name', 'users.username', 'users.photo');
                                 },
                                 'mentions' => function($query) {
                                     $query->select('users.id', 'users.name', 'users.username', 'users.photo');
                                 },
                                 'comments.user' => function ($query) { $query->with('badges'); },
                                 'comments' => function($query) {
                                     $query->withCount(['likes as likes_count'])->latest()->limit(2);
                                 }
                             ])
                             ->withCount(['likes', 'comments'])
                             ->latest()
                             ->paginate($request->input('per_page', 10));
        
                foreach ($posts as $post) {
                    $post->is_liked = auth()->check() ? $post->likes->contains(auth()->id()) : false;
                }
    
                Log::info("Found {$posts->count()} posts for User ID: {$user->id}. Total available: {$posts->total()}");
        
                return response()->json(['success' => true, 'posts' => $posts]);
    
            } catch (\Exception $e) {
                Log::error("Error fetching posts for User ID: {$user->id}. Error: {$e->getMessage()}");
                return response()->json(['success' => false, 'message' => 'Could not retrieve posts.'], 500);
            }
        }
    
        public function update(Request $request)
        {
            $authUser = $request->user();
            Log::info("ProfileUserController@update: Attempting to update profile for User ID: {$authUser->id}");
            Log::info("ProfileUserController@update: Incoming request data: ", $request->all()); // Log all incoming data (including files if any)
    
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'username' => [
                    'sometimes', 'string', 'max:255', 'alpha_dash',
                    Rule::unique('users')->ignore($authUser->id),
                ],
                'bio' => 'nullable|string|max:1000',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);
    
            if ($validator->fails()) {
                Log::warning("ProfileUserController@update: Validation failed for User ID: {$authUser->id}", $validator->errors()->toArray());
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            Log::info("ProfileUserController@update: Validation passed for User ID: {$authUser->id}");
    
            $dataToUpdate = [];
            if ($request->filled('name')) { // Use filled to check if present and not empty
                $dataToUpdate['name'] = $request->name;
                Log::info("ProfileUserController@update: Preparing to update name to: " . $request->name);
            }
            if ($request->filled('username')) {
                $dataToUpdate['username'] = $request->username;
                Log::info("ProfileUserController@update: Preparing to update username to: " . $request->username);
            }
            // For 'bio', if the request sends 'bio' (even if it's an empty string), update it.
            // If 'bio' is not in the request at all, don't touch it.
            if ($request->has('bio')) { // 'has' checks for presence, 'filled' checks for not empty
                $dataToUpdate['bio'] = $request->bio; // This will allow setting bio to empty string to clear it
                Log::info("ProfileUserController@update: Preparing to update bio to: \"" . ($request->bio ?? 'NULL') . "\"");
            }
    
    
            if ($request->hasFile('photo')) {
                Log::info("ProfileUserController@update: Processing profile photo for User ID: {$authUser->id}");
                if ($authUser->photo && Storage::disk('public')->exists($authUser->photo)) {
                    Log::info("ProfileUserController@update: Deleting old profile photo: " . $authUser->photo);
                    Storage::disk('public')->delete($authUser->photo);
                }
                $dataToUpdate['photo'] = $request->file('photo')->store('user_photos', 'public');
                Log::info("ProfileUserController@update: New profile photo stored at: " . $dataToUpdate['photo']);
            }
    
            if ($request->hasFile('cover_photo')) {
                Log::info("ProfileUserController@update: Processing cover photo for User ID: {$authUser->id}");
                if ($authUser->cover_photo && Storage::disk('public')->exists($authUser->cover_photo)) {
                    Log::info("ProfileUserController@update: Deleting old cover photo: " . $authUser->cover_photo);
                    Storage::disk('public')->delete($authUser->cover_photo);
                }
                $dataToUpdate['cover_photo'] = $request->file('cover_photo')->store('user_cover_photos', 'public');
                Log::info("ProfileUserController@update: New cover photo stored at: " . $dataToUpdate['cover_photo']);
            }
            
            Log::info("ProfileUserController@update: Data prepared for update: ", $dataToUpdate);
    
            if (!empty($dataToUpdate)) {
                Log::info("ProfileUserController@update: Updating user data in database for User ID: {$authUser->id}");
                try {
                    $updateResult = $authUser->update($dataToUpdate);
                    if ($updateResult) {
                        Log::info("ProfileUserController@update: User ID: {$authUser->id} updated successfully in DB.");
                    } else {
                        Log::warning("ProfileUserController@update: User ID: {$authUser->id} update operation returned false (no changes or error).");
                    }
                } catch (\Exception $e) {
                    Log::error("ProfileUserController@update: Exception during user update for User ID: {$authUser->id}. Error: {$e->getMessage()}");
                     return response()->json(['success' => false, 'message' => 'Failed to update profile due to a server error.'], 500);
                }
            } else {
                Log::info("ProfileUserController@update: No data to update for User ID: {$authUser->id}.");
            }
    
            $authUser->refresh(); 
            $authUser->load('badges');
            Log::info("ProfileUserController@update: User data after refresh and loading badges for User ID: {$authUser->id}", $authUser->toArray());
    
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user' => $authUser // This will include appended photo_url and cover_photo_url
            ]);
        }
}
