<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\ClassRoom;
use App\Models\StudentAttendanceRecord; // <-- ⭐ إضافة
use Carbon\Carbon; // <-- ⭐ إضافة
use Illuminate\Support\Facades\Log; // <-- إضافة

class StatsController extends Controller
{
    /**
     * جلب الإحصائيات الرئيسية لنظام Edura
     */
    public function index(Request $request)
    {
        // 1. حساب عدد الطلاب
        $students_count = Student::count();

        // 2. حساب عدد المعلمين
        $teachers_count = User::where('user_type', 'teacher')->count(); // (تأكد من 'user_type' أو 'role')

        // 3. حساب الإيرادات (الدخل)
        $revenue = Transaction::where('type', 'income')->sum('amount'); // (تأكد من 'income' أو 'deposit')

        // 4. حساب عدد الفصول النشطة
        $active_classes = ClassRoom::count();

        // --- ⭐ 5. حساب معدل الحضور الحقيقي لليوم ⭐ ---
        $today = Carbon::today();

        // عدد الطلاب الحاضرين اليوم
        $presenceCount = StudentAttendanceRecord::whereDate('created_at', $today)
                            ->where('record_state', 'حضور - presence')
                            ->distinct('student_id') // لضمان عدم حساب الطالب مرتين
                            ->count();
                            
        // عدد الطلاب الغائبين اليوم
        $absenceCount = StudentAttendanceRecord::whereDate('created_at', $today)
                            ->where('record_state', 'غياب - absence')
                            ->distinct('student_id')
                            ->count();

        // إجمالي الطلاب المسجل لهم حضور أو غياب
        $total_recorded = $presenceCount + $absenceCount;

        // حساب النسبة المئوية (تجنب القسمة على صفر)
        // إذا لم يتم تسجيل أي شيء، نفترض أن الحضور 100% (أو 0% حسب منطق العمل)
        $attendance_rate = ($total_recorded > 0) 
                            ? round(($presenceCount / $total_recorded) * 100) 
                            : 100; // افترض 100% إذا لم يسجل غياب
        // --- نهاية حساب الحضور ---


        $statsData = [
            'students_count' => $students_count,
            'teachers_count' => $teachers_count,
            'revenue' => $revenue,
            'attendance_rate' => $attendance_rate, // <-- ⭐ القيمة الحقيقية
            'active_classes' => $active_classes,
        ];
        
        Log::debug('[StatsController] إرسال الإحصائيات إلى Edura', $statsData);

        return response()->json($statsData);
    }
}

