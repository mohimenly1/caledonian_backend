<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CaledonianNotification; // ✨ --- 1. استدعاء المودل الجديد --- ✨
use App\Models\User;
use App\Notifications\GeneralAnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class TargetedNotificationController extends Controller
{
    /**
     * Get a paginated list of parents with their students, filterable by class, section, and teacher.
     */
    public function getParentList(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'teacher_id' => 'nullable|exists:users,id',
            'search' => 'nullable|string|max:255',
        ]);

        $query = User::where('user_type', 'parent')
            ->with(['parentInfo.students' => function ($studentQuery) {
                $studentQuery->with(['class:id,name', 'section:id,name']);
            }]);

        // Filter by Parent Name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $validated['search'] . '%');
        }

        // Filter by Class using the correct relationship
        if ($request->filled('class_id')) {
            $query->whereHas('parentInfo.students.class', function ($q) use ($validated) {
                $q->where('id', $validated['class_id']);
            });
        }

        // Filter by Section
        if ($request->filled('section_id')) {
            $query->whereHas('parentInfo.students.section', function ($q) use ($validated) {
                $q->where('id', $validated['section_id']);
            });
        }
        
        // Filter by Teacher
        if ($request->filled('teacher_id')) {
            $teacher = User::find($validated['teacher_id']);
            $sectionIds = $teacher->teacherCourseAssignments()->pluck('section_id')->unique();

            if ($sectionIds->isNotEmpty()) {
                $query->whereHas('parentInfo.students', function ($q) use ($sectionIds) {
                    $q->whereIn('section_id', $sectionIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        
        $parents = $query->orderBy('name')->paginate($request->input('per_page', 15));
        
        $parents->through(function ($user) {
            $user->parent_details = $user->parentInfo;
            unset($user->parentInfo);
            return $user;
        });


        return response()->json($parents);
    }

    /**
     * Send a targeted announcement to a specific list of users.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image_path' => $imagePath,
            'sent_by_user_id' => Auth::id(),
        ]);

        // ✨ --- بداية التعديل: منطق حفظ الإشعارات --- ✨

        // 2. جلب جميع المستخدمين المستهدفين
        $allTargetedUsers = User::whereIn('id', $validated['user_ids'])->get();
        $notificationsToInsert = [];
        $now = now();

        // 3. تجهيز مصفوفة لحفظ الإشعارات دفعة واحدة
        foreach ($allTargetedUsers as $user) {
            $notificationsToInsert[] = [
                'user_id' => $user->id,
                'title' => $validated['title'],
                'body' => $validated['body'],
                'data' => json_encode([
                    'type' => 'general_announcement',
                    'announcement_id' => $announcement->id,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 4. حفظ جميع الإشعارات في قاعدة البيانات في استعلام واحد
        if (!empty($notificationsToInsert)) {
            CaledonianNotification::insert($notificationsToInsert);
        }

        // 5. فلترة المستخدمين الذين لديهم Fcm token لإرسال الإشعار اللحظي
        $usersWithToken = $allTargetedUsers->whereNotNull('fcm_token');

        if ($usersWithToken->isEmpty()) {
            return response()->json(['message' => 'تم حفظ الإشعار لـ ' . $allTargetedUsers->count() . ' مستخدمًا. (لم يتم إرسال إشعارات لحظية لعدم وجود أجهزة مسجلة)'], 200);
        }

        // 6. إرسال الإشعار اللحظي
        Notification::send($usersWithToken, new GeneralAnnouncementNotification($announcement));

        return response()->json([
            'message' => 'تم حفظ الإشعار وإرساله بنجاح إلى ' . $usersWithToken->count() . ' جهاز.',
        ]);
        // --- نهاية التعديل --- ✨
    }
}

