<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class StudentPurchaseLimitController extends Controller
{
    // دالة لجلب الطلبة بدون حد شراء يومي
    // public function studentsWithoutPurchaseLimit()
    // {
    //     $students = Student::whereNull('daily_allowed_purchase_value')->get();

    //     return response()->json(['students' => $students], 200);
    // }

    // دالة لجلب الطلبة مع البحث والتصفية
    public function index(Request $request)
    {
        $query = Student::query();

        // تطبيق البحث
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%')

                ->orWhere('arabic_name', 'LIKE', '%' . $request->search . '%');
        }

        // تطبيق التصفية والصفحات
        $students = $query->paginate(10);

        return response()->json(['students' => $students], 200);
    }

    // دالة لتحديث الحقل daily_allowed_purchase_value للطالب
    public function updatePurchaseLimit(Request $request, $id)
    {
        $request->validate([
            'daily_allowed_purchase_value' => 'nullable|numeric|min:0'
        ]);

        $student = Student::findOrFail($id);
        $student->daily_allowed_purchase_value = $request->daily_allowed_purchase_value;
        $student->save();

        return response()->json(['message' => 'تم تحديث حد الشراء اليومي بنجاح'], 200);
    }
}
