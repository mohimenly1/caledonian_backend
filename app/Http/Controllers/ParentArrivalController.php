<?php

namespace App\Http\Controllers;

use App\Models\ParentArrival;
use App\Models\ParentInfo;
use App\Models\User;
use App\Notifications\ParentArrivalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ParentArrivalController extends Controller
{

    public function index()
    {
        $arrivals = ParentArrival::with(['parent:id,first_name,last_name', 'scanner:id,name'])
                                ->latest() // للترتيب حسب الأحدث
                                ->take(20) // جلب آخر 20 سجل فقط
                                ->get();
        
        return response()->json($arrivals);
    }

    /**
     * معالجة QR Code ولي الأمر بعد مسحه
     */
    public function processParentQrCode(Request $request)
    {
        // 1. التحقق من صحة البيانات القادمة من الـ QR Code
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // 2. جلب بيانات ولي الأمر وأبنائه بالكامل
        $parent = ParentInfo::with(['user', 'students.class', 'students.section'])
            ->where('user_id', $validated['user_id'])
            ->firstOrFail();

        // --- الاستجابة الفورية للمشرف الذي قام بالمسح ---
        // (هذه البيانات ستظهر له في شاشته مباشرة)
        $responseData = [
            'parent_name' => $parent->first_name . ' ' . $parent->last_name,
            'children' => $parent->students->map(function ($student) {
                return [
                    'name' => $student->name,
                    'class' => $student->class->name ?? 'N/A',
                    'section' => $student->section->name ?? 'N/A',
                ];
            }),
        ];

        // --- الجزء الخاص بالإشعارات للمشرفين الآخرين ---

        // 3. بناء رسالة الإشعار
   // 3. بناء رسالة الإشعار (بالتفاصيل الكاملة)
$childrenDetails = $parent->students->map(function ($student) {
    $className = $student->class->name ?? 'غير محدد';
    $sectionName = $student->section->name ?? 'N/A';
    return "{$student->name} (فصل {$className} - شعبة {$sectionName})";
})->implode('، '); // استخدمنا فاصلة لتكون القائمة أوضح

$message = "وصل ولي الأمر {$parent->first_name} لاستلام أبنائه: {$childrenDetails}.";

        // 4. حفظ سجل الوصول في قاعدة البيانات
        $arrival = ParentArrival::create([
            'parent_id' => $parent->id,
            'scanned_by_user_id' => Auth::id(), // المستخدم الحالي (المشرف)
            'message' => $message,
        ]);

        // 5. جلب جميع المشرفين الذين لديهم fcm_token
        $supervisors = User::where('user_type', 'student supervisor')
                           ->whereNotNull('fcm_token')
                           ->get();
        
        // 6. إرسال الإشعار
        if ($supervisors->isNotEmpty()) {
            Notification::send($supervisors, new ParentArrivalNotification($arrival));
        }

        // 7. إرجاع البيانات للمشرف الذي قام بالمسح
        return response()->json($responseData);
    }
}