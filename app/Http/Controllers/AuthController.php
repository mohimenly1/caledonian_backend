<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\LoginNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'address' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:admin,teacher,staff,student,parent,driver',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'username' => $request->username,
            'email' => $request->email,
            'address' => $request->address,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer', 'user_type' => $user->user_type]);
    }

    // Login an existing user
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string', // ✅ Add this
        ]);
    
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : (is_numeric($request->login) && strlen($request->login) == 10 ? 'phone' : 'username');
    
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];
    
        logger("Login attempt with credentials: ", $credentials);
    
        if (Auth::guard('web')->attempt($credentials)) {
            $user = Auth::user();
    
            if (!$user) {
                logger("Login failed - user not found after Auth::attempt.");
                return response()->json(['message' => 'Login failed, user not found.'], 500);
            }
    
            // ✅ Save FCM token if sent
            if ($request->filled('fcm_token')) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
            }
    
            $token = $user->createToken('authToken')->plainTextToken;
    
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => 'User logged in',
                'new_data' => json_encode($user->username),
                'created_at' => now(),
            ]);
    
            $admins = User::where('user_type', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new LoginNotification($user));
            }
    
            return response()->json(['token' => $token, 'user' => $user,
            'debug_fcm_token_received' => $request->fcm_token,
            'badges' => $user->badges, // Add this line

        ], 200);
        }
    
        logger("Login failed - invalid credentials provided.");
        return response()->json(['message' => 'Invalid credentials.'], 401);
    }
    
    


    public function loginApp(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string', // ✅ Add this
        ]);
    
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : (is_numeric($request->login) && strlen($request->login) == 10 ? 'phone' : 'username');
    
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];
    
        logger("Login attempt with credentials: ", $credentials);
    
        if (Auth::guard('web')->attempt($credentials)) {
            $user = Auth::user();
            $user->last_activity = now(); // Update last activity immediately
            $user->save();
    
            if (!$user) {
                logger("Login failed - user not found after Auth::attempt.");
                return response()->json(['message' => 'Login failed, user not found.'], 500);
            }
    
            // ✅ Save FCM token if sent
            if ($request->filled('fcm_token')) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
            }
    
            $token = $user->createToken('authToken')->plainTextToken;
    
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => 'User logged in',
                'new_data' => json_encode($user->username),
                'created_at' => now(),
            ]);
    
            $admins = User::where('user_type', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new LoginNotification($user));
            }
    
           // In both login and loginApp functions, update the return response to include badges
           return response()->json([
            'token' => $token,
            'user' => $user,
            'badges' => $user->badges, // Explicitly include badges
            'is_online' => true, // Force online status on login
            'last_activity' => $user->last_activity,

            'debug_fcm_token_received' => $request->fcm_token,
        ], 200);
        }
    
        logger("Login failed - invalid credentials provided.");
        return response()->json(['message' => 'Invalid credentials.'], 401);
    }
    
    
    
    

    // Logout the user
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function logoutApp(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
