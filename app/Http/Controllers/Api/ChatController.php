<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewGroupMessageNotification;
use App\Notifications\NewPrivateMessageNotification;
use App\Notifications\GroupActivityNotification;
use Illuminate\Http\Request;
use App\Models\Student; // Add this line
use App\Models\MessageStatus;

use App\Models\Employee; // Add if you're using Employee model
use App\Models\Section; // Add if you're using Section model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
class ChatController extends Controller
{
    // List all public groups and groups the user is member of

    // Add this method to your ChatController


// Add this temporary debug code to your createSystemMessage method
private function createSystemMessage(ChatGroup $group, User $actor, string $content, string $systemMessageType)
{
    try {
        $message = Message::create([
            'sender_id' => $actor->id,
            'chat_group_id' => $group->id,
            'content' => $content,
            'is_system_message' => true,
            'system_message_type' => $systemMessageType,
            'message_type' => 'system'
        ]);

        // Create message status for all members
        $statuses = $group->members->map(function($member) use ($message) {
            return [
                'message_id' => $message->id,
                'user_id' => $member->id,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        MessageStatus::insert($statuses);
        
        return $message;
    } catch (\Exception $e) {
        Log::error('Failed to create system message', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

// Helper method to format member names
private function formatMemberNames(array $userIds): string
{
    $users = User::whereIn('id', $userIds)->get();
    
    if ($users->count() === 1) {
        return $users->first()->name;
    }
    
    if ($users->count() <= 3) {
        return $users->pluck('name')->join(', ', ' and ');
    }
    
    return $users->first()->name . ' and ' . ($users->count() - 1) . ' others';
}

    public function getAllUsers()
    {
        $users = User::with(['student.class', 'student.section', 'employee.classes'])
            ->whereNotIn('id', function($query) {
                $query->select('blocked_id')
                    ->from('user_blocks')
                    ->where('blocker_id', Auth::id());
            })
            ->get();
    
        return response()->json(['data' => $users]);
    }

/**
 * Filter users by class/section/type
 */
public function filterUsers(Request $request)
{
    $query = User::query()->with(['student.class', 'student.section', 'employee.classes']);

    if ($request->class_id) {
        $query->where(function($q) use ($request) {
            // Students in the class
            $q->whereHas('student', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
                
                if ($request->section_id) {
                    $q->where('section_id', $request->section_id);
                }
            });
            
            // Teachers assigned to the class
            $q->orWhereHas('employee', function($q) use ($request) {
                $q->where('is_teacher', true)
                  ->whereHas('classes', function($q) use ($request) {
                      $q->where('class_id', $request->class_id);
                  });
                
                if ($request->section_id) {
                    $q->whereHas('sections', function($q) use ($request) {
                        $q->where('section_id', $request->section_id);
                    });
                }
            });
        });
    }

    if ($request->user_type) {
        $query->where('user_type', $request->user_type);
        
        // For parents, we need to find parents of students in the selected class/section
        if ($request->user_type === 'parent') {
            $query->whereHas('parent', function($q) use ($request) {
                $q->whereHas('students', function($q) use ($request) {
                    if ($request->class_id) {
                        $q->where('class_id', $request->class_id);
                    }
                    if ($request->section_id) {
                        $q->where('section_id', $request->section_id);
                    }
                });
            });
        }
    }

    $users = $query->get();

    return response()->json(['data' => $users]);
}
public function getUnreadCount(ChatGroup $group)
{
    $user = Auth::user();
    
    // Check if user is member of the group
    if (!$group->members()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
    $unreadCount = $group->messages()
        ->where('sender_id', '!=', $user->id)
        ->whereDoesntHave('statuses', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('is_read', true);
        })
        ->count();
    
    return response()->json(['count' => $unreadCount]);
}


public function index()
{
    $user = Auth::user();

    // جلب المجموعات التي ينتمي إليها المستخدم
    $userGroupsQuery = $user->groups();
    
    // جلب المجموعات العامة التي لا ينتمي إليها المستخدم
    $publicGroupsQuery = ChatGroup::where('is_public', true)
                               ->whereDoesntHave('members', function ($query) use ($user) {
                                   $query->where('user_id', $user->id);
                               });

    // --- التعديل الرئيسي هنا ---
    // بدلاً من تحميل جميع بيانات الأعضاء، نقوم فقط بحساب عددهم
    $userGroups = $userGroupsQuery->withCount('members')->get();
    $publicGroups = $publicGroupsQuery->withCount('members')->get();

    return response()->json([
        'user_groups' => $userGroups,
        'public_groups' => $publicGroups,
    ]);
}

    // Create a new group
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'required|boolean',
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $user = Auth::user();
        
        if (!$user->hasBadge('moder') && $request->is_public) {
            return response()->json(['message' => 'Only moderators can create public groups'], 403);
        }
    
        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'creator_id' => $user->id,
            'is_public' => $request->is_public,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
        ];
    
        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('group_images', 'public');
            $data['image_path'] = $path;
        } elseif ($request->class_id) {
            // Set default class/section image if no image provided
            $data['image_path'] = 'cis_group.png';
        } else {
            $data['image_path'] = 'cis_group.png';
        }
    
        $group = ChatGroup::create($data);
    
        // Add creator as admin member
        $group->members()->attach($user->id, ['is_admin' => true]);
    
        // If it's a class/section group, add members automatically
        if ($request->class_id) {
            $this->addClassMembersToGroup($group, $request->class_id, $request->section_id);
        }
    
        return response()->json($group, 201);
    }

    private function addClassMembersToGroup($group, $classId, $sectionId = null)
    {
        // Get all students in the class/section
        $query = Student::where('class_id', $classId);
        
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        
        $students = $query->with('user')->get();
        
        // Get all teachers assigned to this class/section
        $teachers = Employee::where('is_teacher', true)
            ->whereHas('classes', function($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->when($sectionId, function($q) use ($sectionId) {
                $q->whereHas('sections', function($q) use ($sectionId) {
                    $q->where('section_id', $sectionId);
                });
            })
            ->with('user')
            ->get();
        
        // Prepare sync data
        $syncData = [];
        $addedUserIds = [];
        
        // Add students and their parents
        foreach ($students as $student) {
            if ($student->user) {
                $syncData[$student->user->id] = ['is_admin' => false];
                $addedUserIds[] = $student->user->id;
            }
            
            if ($student->parent && $student->parent->user) {
                $syncData[$student->parent->user->id] = ['is_admin' => false];
                $addedUserIds[] = $student->parent->user->id;
            }
        }
        
        // Add teachers as admins
        foreach ($teachers as $teacher) {
            if ($teacher->user) {
                $syncData[$teacher->user->id] = ['is_admin' => true];
                $addedUserIds[] = $teacher->user->id;
            }
        }
        
        // Sync all members at once
        $group->members()->syncWithoutDetaching($syncData);
        
        // Create system message
        $addedNames = $this->formatMemberNames($addedUserIds);
        $this->createSystemMessage(
            $group,
            Auth::user(),
            "Class members added to the group: $addedNames",
            'members_added'
        );
        
        // Notify new members
        foreach ($addedUserIds as $userId) {
            $user = User::find($userId);
            if ($user && $user->fcm_token) {
                $user->notify(new GroupActivityNotification(
                    $group,
                    'added you to the class group',
                    Auth::user()
                ));
            }
        }
    }


    public function getImageUrlAttribute()
{
    if (!$this->image_path) {
        return asset('cis_group.png'); // Make sure this default image exists
    }
    
    // Check if the path is already a URL
    if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
        return $this->image_path;
    }
    
    return Storage::url($this->image_path);
}
    // Get group details
    public function show(ChatGroup $group)
    {
        $user = Auth::user();
        
        if (!$group->is_public && !$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $group->load(['members' => function($query) {
            $query->where('is_blocked', false)
                  ->select(['users.id', 'users.name', 'users.user_type', 'users.last_activity'])
                  ->with('badges');
        }, 'creator']);
        
        return response()->json($group);
    }

    // Update group (only for admins/moder)
    public function update(Request $request, ChatGroup $group)
    {
        $user = Auth::user();
        
        // Check if user is admin of the group or has moder badge
        $isAdmin = $group->members()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->exists();
            
        if (!$isAdmin && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'sometimes|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Only moder can change public/private status
        if ($request->has('is_public') && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Only moderators can change group visibility'], 403);
        }
        
        $group->update($request->only(['name', 'description', 'is_public']));
        
        return response()->json($group);
    }

    // Delete group (only for creator/moder)
    public function destroy(ChatGroup $group)
    {
        $user = Auth::user();
        
        if ($group->creator_id !== $user->id && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $group->delete();
        
        return response()->json(['message' => 'Group deleted successfully']);
    }

    // Add members to group (for admins/moder)
    public function addMembers(Request $request, ChatGroup $group)
    {
        $user = Auth::user();
        
        // Check if user is admin of the group or has moder badge
        $isAdmin = $group->members()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->exists();
            
        if (!$isAdmin && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'as_admin' => 'sometimes|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $syncData = [];
        foreach ($request->user_ids as $userId) {
            $syncData[$userId] = ['is_admin' => $request->as_admin ?? false];
        }
        
        $group->members()->syncWithoutDetaching($syncData);
    
        // Create system message
        $addedNames = $this->formatMemberNames($request->user_ids);
        $adminStatus = $request->as_admin ? ' as admin' : '';
        $this->createSystemMessage(
            $group,
            $user,
            "{$user->name} added $addedNames to the group{$adminStatus}",
            'member_added'
        );
    
        foreach ($request->user_ids as $userId) {
            $newMember = User::find($userId);
            if ($newMember && $newMember->fcm_token) {
                $newMember->notify(new GroupActivityNotification(
                    $group,
                    'added you to the group',
                    $user
                ));
            }
        }
    
        // Notify existing members about new members
        $existingMembers = $group->members()
            ->whereNotIn('user_id', $request->user_ids)
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->get()
            ->pluck('user');
    
        foreach ($existingMembers as $member) {
            if ($member->fcm_token) {
                $member->notify(new GroupActivityNotification(
                    $group,
                    'added new members to the group',
                    $user
                ));
            }
        }
        
        return response()->json(['message' => 'Members added successfully']);
    }

    // Remove members from group (for admins/moder)
    public function removeMembers(Request $request, ChatGroup $group)
    {
        $user = Auth::user();
        
        // Check if user is admin of the group or has moder badge
        $isAdmin = $group->members()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->exists();
            
        if (!$isAdmin && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Can't remove yourself unless you're moder
        if (in_array($user->id, $request->user_ids)) {
            if (!$user->hasBadge('moder')) {
                return response()->json(['message' => 'You cannot remove yourself'], 403);
            }
        }
        
        $removedNames = $this->formatMemberNames($request->user_ids);
        $group->members()->detach($request->user_ids);
        
        // Create system message
        $this->createSystemMessage(
            $group,
            $user,
            "{$user->name} removed $removedNames from the group",
            'member_removed'
        );
        
        return response()->json(['message' => 'Members removed successfully']);
    }

    // Block/unblock members in group (for admins/moder)
    public function blockMembers(Request $request, ChatGroup $group)
    {
        $user = Auth::user();
        
        // Check if user is admin of the group or has moder badge
        $isAdmin = $group->members()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->exists();
            
        if (!$isAdmin && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'block' => 'required|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Can't block yourself
        if (in_array($user->id, $request->user_ids)) {
            return response()->json(['message' => 'You cannot block yourself'], 403);
        }
        
        foreach ($request->user_ids as $userId) {
            $group->members()->updateExistingPivot($userId, [
                'is_blocked' => $request->block,
                'blocked_by' => $user->id,
                'blocked_at' => $request->block ? now() : null,
            ]);
        }
        
        $action = $request->block ? 'blocked' : 'unblocked';
        $userNames = $this->formatMemberNames($request->user_ids);
        
        // Create system message
        $this->createSystemMessage(
            $group,
            $user,
            "{$user->name} $action $userNames in the group",
            'member_' . $action
        );
        
        return response()->json(['message' => "Members {$action} successfully"]);
    }

    // Promote/demote members (for admins/moder)
    public function updateMemberRole(Request $request, ChatGroup $group)
    {
        $user = Auth::user();
        
        // Check if user is admin of the group or has moder badge
        $isAdmin = $group->members()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->exists();
            
        if (!$isAdmin && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'as_admin' => 'required|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Can't demote yourself unless you're moder
        if (in_array($user->id, $request->user_ids) && !$request->as_admin) {
            if (!$user->hasBadge('moder')) {
                return response()->json(['message' => 'You cannot demote yourself'], 403);
            }
        }
        
        foreach ($request->user_ids as $userId) {
            $group->members()->updateExistingPivot($userId, [
                'is_admin' => $request->as_admin,
            ]);
        }
        
        $action = $request->as_admin ? 'promoted' : 'demoted';
        $userNames = $this->formatMemberNames($request->user_ids);
        
        // Create system message
        $this->createSystemMessage(
            $group,
            $user,
            "{$user->name} $action $userNames to " . ($request->as_admin ? 'admin' : 'member'),
            'member_role_changed'
        );
        
        return response()->json(['message' => "Members {$action} successfully"]);
    }

    // Join public group
    public function joinGroup(ChatGroup $group)
    {
        $user = Auth::user();
        
        if (!$group->is_public) {
            return response()->json(['message' => 'This is not a public group'], 403);
        }
        
        if ($group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are already a member'], 400);
        }
        
        $group->members()->attach($user->id, ['is_admin' => false]);
        
        // Create system message
        $this->createSystemMessage(
            $group,
            $user,
            "{$user->name} joined the group",
            'member_joined'
        );
        
        return response()->json(['message' => 'Joined group successfully']);
    }

    // Leave group
    public function leaveGroup(ChatGroup $group)
    {
        $user = Auth::user();
        
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this group'], 400);
        }
        
        $group->members()->detach($user->id);
        
        // Create system message if group has more than 1 member
        if ($group->members()->count() > 0) {
            $this->createSystemMessage(
                $group,
                $user,
                "{$user->name} left the group",
                'member_left'
            );
        }
        
        return response()->json(['message' => 'Left group successfully']);
    }

    // Get group messages
 public function getMessages(ChatGroup $group)
{
    $user = Auth::user();
    
    // تحقق من صلاحية المستخدم
    if (!$group->is_public && !$group->members()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'Unauthorized. This is a private group.'], 403);
    }

    // ✅ جلب جميع الرسائل بترتيب زمني تصاعدي بدون paginate
    $allMessages = $group->messages()
        ->with([
            'sender:id,name,photo,last_activity',
            'statuses' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();

    // ✅ تعليم كل الرسائل المستلمة كمقروءة
    $group->messages()
        ->where('sender_id', '!=', $user->id)
        ->where('is_read', false)
        ->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

    // ✅ نصنع نفس شكل الاستجابة السابق للـ Flutter (يحاكي paginate)
    $response = [
        'current_page' => 1,
        'data' => $allMessages,
        'first_page_url' => null,
        'from' => 1,
        'last_page' => 1,
        'last_page_url' => null,
        'links' => [],
        'next_page_url' => null,
        'path' => request()->url(),
        'per_page' => $allMessages->count(),
        'prev_page_url' => null,
        'to' => $allMessages->count(),
        'total' => $allMessages->count(),
    ];

    Log::info("Messages returned (no paginate)", [
        'group_id' => $group->id,
        'count' => $allMessages->count()
    ]);

    return response()->json($response);
}


    // Send message to group
    public function sendMessage(Request $request, ChatGroup $group)
    {
        $user = Auth::user();
        
        // Check if user is member of the group and not blocked
        $member = $group->members()
            ->where('user_id', $user->id)
            ->first();
            
        if (!$member || $member->pivot->is_blocked) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'content' => 'required_without:media|string',
            'media' => 'required_without:content|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,pdf,doc,docx,mp3,wav,xlsx,webm,aac,adts,m4a',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $messageData = [
            'sender_id' => $user->id,
            'chat_group_id' => $group->id,
            'content' => $request->content,
            'message_type' => 'text', // default to text
        ];
        
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('chat_media', $originalName, 'public');
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
        
            $messageData['media_path'] = $path;
        
            // Determine message type
            if (in_array($extension, ['aac', 'm4a', 'mp3', 'wav'])) {
                $messageData['message_type'] = 'audio';
            } elseif (str_starts_with($mimeType, 'image/')) {
                $messageData['message_type'] = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $messageData['message_type'] = 'video';
            } else {
                $messageData['message_type'] = 'document';
            }
        }
        
        $message = Message::create($messageData);
        
        // Create message status for all members except sender
        $members = $group->members()
            ->where('user_id', '!=', $user->id)
            ->where('is_blocked', false)
            ->pluck('user_id');
            
        $statuses = [];
        foreach ($members as $memberId) {
            $statuses[] = [
                'message_id' => $message->id,
                'user_id' => $memberId,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($statuses)) {
            MessageStatus::insert($statuses);
        }
        
        // Notify all group members except sender
        $recipients = $group->members()
            ->where('user_id', '!=', $user->id)
            ->where('is_blocked', false)
            ->get();
        
        $content = $message->content ?? $this->getMediaTypeForContent($message->message_type) ?? 'New message';
        
        foreach ($recipients as $recipient) {
            // Store notification in database
            $recipient->caledonianNotifications()->create([
                'title' => 'New message in ' . $group->name,
                'body' => $user->name . ': ' . $content,
                'data' => [
                    'type' => 'group_message',
                    'group_id' => $group->id,
                    'message_id' => $message->id,
                    'sender_id' => $user->id,
                ],
            ]);
            
            // Send FCM notification if token exists
            if ($recipient->fcm_token) {
                $recipient->notify(new NewGroupMessageNotification($message));
            }
        }
        
        return response()->json($message, 201);
    }
    
    protected function getMediaTypeForContent(string $messageType): ?string
    {
        return match($messageType) {
            'image' => 'Sent an image',
            'video' => 'Sent a video',
            'audio' => 'Sent a voice message',
            'document' => 'Sent a document',
            default => null,
        };
    }


    protected function convertWebmToAac($webmFile)
    {
        $tempWebmPath = $webmFile->getRealPath();
        $tempAacPath = tempnam(sys_get_temp_dir(), 'converted') . '.aac';
        
        // Use ffmpeg to convert
        $ffmpegCommand = "ffmpeg -i {$tempWebmPath} -c:a aac -b:a 128k {$tempAacPath}";
        exec($ffmpegCommand, $output, $returnCode);
        
        Log::info('FFmpeg conversion', [
            'command' => $ffmpegCommand,
            'output' => $output,
            'return_code' => $returnCode,
            'temp_files' => [
                'input' => $tempWebmPath,
                'output' => $tempAacPath
            ]
        ]);
        
        if ($returnCode !== 0) {
            throw new \Exception("Failed to convert audio file: " . implode("\n", $output));
        }
        
        // Generate a new filename with .aac extension
        $filename = pathinfo($webmFile->getClientOriginalName(), PATHINFO_FILENAME) . '.aac';
        
        // Store the converted file with the correct extension
        $storagePath = Storage::disk('public')->putFileAs(
            'chat_media', 
            new File($tempAacPath), 
            $filename
        );
        
        // Clean up temp files
        unlink($tempAacPath);
        
        return $storagePath;
    }

    // Get private messages between users
    public function getPrivateMessages(User $recipient)
    {
        $user = Auth::user();
        
        // Check if users have blocked each other
        if ($user->blockedUsers()->where('blocked_id', $recipient->id)->exists() ||
            $user->blockedByUsers()->where('blocker_id', $recipient->id)->exists()) {
            return response()->json(['message' => 'Messages are blocked'], 403);
        }
        
        $messages = Message::where(function($query) use ($user, $recipient) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $recipient->id);
            })
            ->orWhere(function($query) use ($user, $recipient) {
                $query->where('sender_id', $recipient->id)
                    ->where('recipient_id', $user->id);
            })
            ->with(['sender', 'statuses' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        // Mark received messages as read
        Message::where('recipient_id', $user->id)
            ->where('sender_id', $recipient->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
            
        return response()->json($messages);
    }

    // Send private message
    public function sendPrivateMessage(Request $request, User $recipient)
    {
        $user = Auth::user();
        
        // Check if users have blocked each other
        if ($user->blockedUsers()->where('blocked_id', $recipient->id)->exists() ||
            $user->blockedByUsers()->where('blocker_id', $recipient->id)->exists()) {
            return response()->json(['message' => 'You cannot message this user'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'content' => 'required_without:media|string',
            'media' => 'required_without:content|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,pdf,doc,docx,mp3,wav',
            'message_type' => 'sometimes|in:text,image,video,audio,document',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $messageData = [
            'sender_id' => $user->id,
            'recipient_id' => $recipient->id,
            'content' => $request->content,
            'message_type' => $request->message_type ?? 'text',
        ];
        
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->store('chat_media', 'public');
            $messageData['media_path'] = $path;
            
            // Determine message type based on file mime type
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $messageData['message_type'] = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $messageData['message_type'] = 'video';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $messageData['message_type'] = 'audio';
            } else {
                $messageData['message_type'] = 'document';
            }
        }
        
        $message = Message::create($messageData);
        
        // Create message status
        MessageStatus::create([
            'message_id' => $message->id,
            'user_id' => $recipient->id,
            'is_read' => false,
        ]);
        
        // Broadcast event for real-time update
        broadcast(new NewPrivateMessageNotification($message))->toOthers();
        
        return response()->json($message, 201);
    }

    // Delete message (only for sender or moder)
    public function deleteMessage(Message $message)
    {
        $user = Auth::user();
        
        if ($message->sender_id !== $user->id && !$user->hasBadge('moder')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $message->delete();
        
        return response()->json(['message' => 'Message deleted successfully']);
    }
}