<?php
// app/Http/Controllers/Api/ChatGroupController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class EduraChatGroupController extends Controller
{
    // في School App - EduraChatGroupController - إضافة دوال جديدة

/**
 * جلب رسائل مجموعة محددة مع Pagination
 */
public function getGroupMessages($groupId, Request $request)
{
    try {
        $group = ChatGroup::findOrFail($groupId);

        $query = Message::where('chat_group_id', $groupId)
            ->with(['sender:id,name,photo']) // ✅ تحديد الأعمدة المطلوبة فقط
            ->orderBy('created_at', 'desc');

        // ✅ إضافة eager loading للرسالة المردود عليها بشكل منفصل
        // لتجنب مشاكل withTrashed() في eager loading
        $query->with(['repliedMessage' => function($q) {
            $q->withTrashed(); // ✅ استخدام withTrashed() داخل callback
        }]);

        // تطبيق فلترة البحث
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhereHas('sender', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // فلترة نوع الرسالة
        if ($request->has('message_type') && $request->message_type) {
            if ($request->message_type === 'text') {
                $query->where('message_type', 'text');
            } elseif ($request->message_type === 'media') {
                $query->where('message_type', '!=', 'text')->whereNotNull('media_path');
            } elseif ($request->message_type === 'system') {
                $query->where('is_system_message', true);
            }
        }

        // التقسيم إلى صفحات
        $perPage = $request->per_page ?? 15;
        $messages = $query->paginate($perPage);

        // تنسيق البيانات للاستجابة
        $formattedMessages = $messages->map(function($message) {
            // ✅ التحقق من وجود repliedMessage قبل الوصول إليها
            $repliedMessageData = null;
            if ($message->repliedMessage) {
                $repliedMessageData = [
                    'id' => $message->repliedMessage->id,
                    'content' => $message->repliedMessage->content,
                    'sender_name' => $message->repliedMessage->sender?->name ?? 'مستخدم محذوف'
                ];
            }

            return [
                'id' => $message->id,
                'content' => $message->content,
                'message_type' => $message->message_type,
                'media_path' => $message->media_path,
                'is_system_message' => $message->is_system_message,
                'system_message_type' => $message->system_message_type,
                'is_edited' => $message->is_edited,
                'edited_at' => $message->edited_at?->format('Y-m-d H:i:s'),
                'sender_id' => $message->sender_id, // ✅ إضافة sender_id
                'sender_name' => $message->sender ? $message->sender->name : 'مستخدم محذوف',
                'sender_avatar' => $message->sender?->photo,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'reply_to_message' => $repliedMessageData // ✅ استخدام البيانات المحضرة
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedMessages,
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching group messages: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(), // ✅ إضافة trace للتحقق
            'group_id' => $groupId
        ]);

        return response()->json([
            'success' => false,
            'message' => 'فشل في جلب رسائل المجموعة',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * جلب إحصائيات رسائل المجموعة
 */
public function getGroupMessagesStats($groupId)
{
    try {
        $group = ChatGroup::findOrFail($groupId);

        $totalMessages = $group->messages()->count();
        $todayMessages = $group->messages()->whereDate('created_at', today())->count();
        $mediaMessages = $group->messages()->whereNotNull('media_path')->count();
        $systemMessages = $group->messages()->where('is_system_message', true)->count();

        // النشاط اليومي للأسبوع الأخير
        $dailyActivity = Message::where('chat_group_id', $groupId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // أكثر الأعضاء نشاطاً
        $topMembers = Message::where('chat_group_id', $groupId)
            ->where('is_system_message', false)
            ->selectRaw('sender_id, COUNT(*) as message_count')
            ->with('sender')
            ->groupBy('sender_id')
            ->orderBy('message_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'sender_name' => $item->sender ? $item->sender->name : 'مستخدم محذوف',
                    'message_count' => $item->message_count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_messages' => $totalMessages,
                'today_messages' => $todayMessages,
                'media_messages' => $mediaMessages,
                'system_messages' => $systemMessages,
                'daily_activity' => $dailyActivity,
                'top_members' => $topMembers
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error fetching group messages stats: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'فشل في جلب إحصائيات الرسائل'
        ], 500);
    }
}
    /**
     * جلب إحصائيات المجموعات
     */
    public function getChatGroupsStats(Request $request)
    {
        try {
            // إحصائيات أساسية عن المجموعات
            $totalGroups = ChatGroup::count();
            $publicGroups = ChatGroup::where('is_public', true)->count();
            $privateGroups = ChatGroup::where('is_public', false)->count();

            // إجمالي الرسائل في جميع المجموعات
            $totalMessages = Message::whereNotNull('chat_group_id')->count();

            // المجموعات النشطة اليوم (التي بها رسائل اليوم)
            $activeGroupsToday = ChatGroup::whereHas('messages', function($query) {
                $query->whereDate('created_at', today());
            })->count();

            // إحصائيات المجموعات حسب الفصول
            $groupsByClass = ChatGroup::with('class')
                ->select('class_id', DB::raw('COUNT(*) as count'))
                ->whereNotNull('class_id')
                ->groupBy('class_id')
                ->get()
                ->map(function($item) {
                    return [
                        'class_name' => $item->class ? $item->class->name : 'غير محدد',
                        'count' => $item->count
                    ];
                });

            // أحدث المجموعات نشاطاً
            $recentActiveGroups = ChatGroup::withCount(['messages', 'members'])
                ->with(['class', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'class_name' => $group->class ? $group->class->name : 'عام',
                        'members_count' => $group->members_count,
                        'messages_count' => $group->messages_count,
                        'last_activity' => $group->messages()->latest()->first()?->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_groups' => $totalGroups,
                    'public_groups' => $publicGroups,
                    'private_groups' => $privateGroups,
                    'total_messages' => $totalMessages,
                    'active_groups_today' => $activeGroupsToday,
                    'groups_by_class' => $groupsByClass,
                    'recent_active_groups' => $recentActiveGroups,
                    'stats_generated_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching chat groups stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب إحصائيات المجموعات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب قائمة المجموعات مع الفلترة
     */
    public function getChatGroups(Request $request)
    {
        try {
            $query = ChatGroup::withCount(['messages', 'members'])
                ->with(['class', 'section', 'creator'])
                ->orderBy('created_at', 'desc');

            // تطبيق الفلترة
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->has('type') && $request->type) {
                if ($request->type === 'public') {
                    $query->where('is_public', true);
                } elseif ($request->type === 'private') {
                    $query->where('is_public', false);
                }
            }

            if ($request->has('class') && $request->class) {
                $query->whereHas('class', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->class}%");
                });
            }

            // التقسيم إلى صفحات
            $perPage = $request->per_page ?? 15;
            $groups = $query->paginate($perPage);

            // تنسيق البيانات للاستجابة
            $formattedGroups = $groups->map(function($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'is_public' => $group->is_public,
                    'chat_disabled_for_parents' => (bool) $group->chat_disabled_for_parents, // ✅ إضافة chat_disabled_for_parents
                    'creator_name' => $group->creator ? $group->creator->name : 'غير معروف',
                    'class_name' => $group->class ? $group->class->name : 'عام',
                    'section_name' => $group->section ? $group->section->name : null,
                    'members_count' => $group->members_count,
                    'messages_count' => $group->messages_count,
                    'image_url' => $group->image_url,
                    'created_at' => $group->created_at->format('Y-m-d H:i:s'),
                    'last_activity' => $group->messages()->latest()->first()?->created_at?->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedGroups,
                'meta' => [
                    'current_page' => $groups->currentPage(),
                    'last_page' => $groups->lastPage(),
                    'per_page' => $groups->perPage(),
                    'total' => $groups->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching chat groups: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب المجموعات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب تفاصيل مجموعة محددة
     */
    public function getGroupDetails($id)
    {
        try {
            $group = ChatGroup::withCount(['messages', 'members'])
                ->with([
                    'class',
                    'section',
                    'creator',
                    'members' => function($query) {
                        $query->wherePivot('is_blocked', false);
                    },
                    'messages' => function($query) {
                        $query->with('sender')
                              ->orderBy('created_at', 'desc')
                              ->limit(10);
                    }
                ])
                ->findOrFail($id);

            // إحصائيات إضافية
            $todayMessages = $group->messages()
                ->whereDate('created_at', today())
                ->count();

            $activeMembers = $group->members()
                ->wherePivot('is_blocked', false)
                ->count();

            $adminMembers = $group->members()
                ->wherePivot('is_admin', true)
                ->wherePivot('is_blocked', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'group' => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'is_public' => $group->is_public,
                        'chat_disabled_for_parents' => (bool) $group->chat_disabled_for_parents, // ✅ إضافة chat_disabled_for_parents
                        'creator' => $group->creator,
                        'class' => $group->class,
                        'section' => $group->section,
                        'image_url' => $group->image_url,
                        'created_at' => $group->created_at->format('Y-m-d H:i:s')
                    ],
                    'stats' => [
                        'total_messages' => $group->messages_count,
                        'total_members' => $group->members_count,
                        'today_messages' => $todayMessages,
                        'active_members' => $activeMembers,
                        'admin_members' => $adminMembers
                    ],
                    'recent_messages' => $group->messages,
                    'members' => $group->members
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching group details: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب تفاصيل المجموعة'
            ], 404);
        }
    }

    /**
     * ✅ جلب مجموعات المعلم (Chat Groups) التي ينتمي إليها
     */
    public function getTeacherChatGroups($teacherId, Request $request)
    {
        try {
            Log::info('[EduraChatGroupController@getTeacherChatGroups] Request received', [
                'teacher_id' => $teacherId,
                'request_params' => $request->all(),
            ]);

            // ✅ جلب المجموعات التي يكون المعلم عضواً فيها
            // ✅ استخدام whereHas للبحث في pivot table
            $query = ChatGroup::whereHas('members', function($q) use ($teacherId) {
                $q->where('users.id', $teacherId)
                  ->where(function($subQ) {
                      $subQ->where('group_members.is_blocked', false)
                           ->orWhereNull('group_members.is_blocked');
                  });
            })
            ->withCount(['messages', 'members'])
            ->with(['class', 'section', 'creator'])
            ->orderBy('created_at', 'desc');

            // ✅ Log للتحقق من الـ query
            Log::debug('[EduraChatGroupController@getTeacherChatGroups] Query built', [
                'teacher_id' => $teacherId,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            // ✅ Test query - جلب عدد المجموعات قبل pagination
            $totalBeforePagination = $query->count();
            Log::info('[EduraChatGroupController@getTeacherChatGroups] Total groups before pagination', [
                'teacher_id' => $teacherId,
                'total' => $totalBeforePagination,
            ]);

            // ✅ تطبيق الفلترة
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->has('type') && $request->type) {
                if ($request->type === 'public') {
                    $query->where('is_public', true);
                } elseif ($request->type === 'private') {
                    $query->where('is_public', false);
                }
            }

            if ($request->has('class') && $request->class) {
                $query->whereHas('class', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->class}%");
                });
            }

            // ✅ التقسيم إلى صفحات
            $perPage = $request->per_page ?? 15;
            $groups = $query->paginate($perPage);

            Log::info('[EduraChatGroupController@getTeacherChatGroups] Groups found', [
                'teacher_id' => $teacherId,
                'groups_count' => $groups->count(),
                'total' => $groups->total(),
            ]);

            // ✅ تنسيق البيانات للاستجابة
            $formattedGroups = $groups->map(function($group) use ($teacherId) {
                // ✅ جلب دور المعلم في المجموعة (admin أو member)
                $memberPivot = $group->members()
                    ->where('users.id', $teacherId)
                    ->first();

                $isAdmin = $memberPivot ? (bool) $memberPivot->pivot->is_admin : false;

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'is_public' => $group->is_public,
                    'chat_disabled_for_parents' => (bool) $group->chat_disabled_for_parents,
                    'creator_name' => $group->creator ? $group->creator->name : 'غير معروف',
                    'class_id' => $group->class_id,
                    'class_name' => $group->class ? $group->class->name : 'عام',
                    'section_id' => $group->section_id,
                    'section_name' => $group->section ? $group->section->name : null,
                    'members_count' => $group->members_count,
                    'messages_count' => $group->messages_count,
                    'image_url' => $group->image_url,
                    'created_at' => $group->created_at->format('Y-m-d H:i:s'),
                    'last_activity' => $group->messages()->latest()->first()?->created_at?->format('Y-m-d H:i:s'),
                    'is_admin' => $isAdmin, // ✅ إضافة دور المعلم في المجموعة
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedGroups,
                'meta' => [
                    'current_page' => $groups->currentPage(),
                    'last_page' => $groups->lastPage(),
                    'per_page' => $groups->perPage(),
                    'total' => $groups->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching teacher chat groups: ' . $e->getMessage(), [
                'teacher_id' => $teacherId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب مجموعات المعلم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
