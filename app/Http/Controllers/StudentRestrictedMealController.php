<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Meal;
use App\Models\StudentRestrictedMeal;
use Illuminate\Http\Request;

class StudentRestrictedMealController extends Controller
{
    // جلب الوجبات الممنوعة لطالب معين
    public function index($studentId)
    {
        $restrictedMeals = StudentRestrictedMeal::where('student_id', $studentId)
            ->with('meal') // للربط مع معلومات الوجبة
            ->get();

        return response()->json($restrictedMeals);
    }

    // إضافة وجبة ممنوعة لطالب معين
    public function store(Request $request)
    {
        
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'meal_id' => 'required|exists:meals,id',
        ]);

        // التحقق إذا كانت الوجبة موجودة مسبقًا
        $existingRestriction = StudentRestrictedMeal::where('student_id', $request->student_id)
            ->where('meal_id', $request->meal_id)
            ->first();

        if ($existingRestriction) {
            return response()->json(['message' => 'هذه الوجبة ممنوعة بالفعل لهذا الطالب'], 409);
        }

        $restrictedMeal = StudentRestrictedMeal::create([
            'student_id' => $request->student_id,
            'meal_id' => $request->meal_id,
        ]);

        return response()->json($restrictedMeal, 201);
    }

    // حذف وجبة ممنوعة لطالب معين
    public function destroy($studentId, $mealId)
    {
        $restrictedMeal = StudentRestrictedMeal::where('student_id', $studentId)
            ->where('meal_id', $mealId)
            ->first();

        if (!$restrictedMeal) {
            return response()->json(['message' => 'الوجبة الممنوعة غير موجودة'], 404);
        }

        $restrictedMeal->delete();

        return response()->json(['message' => 'تم حذف الوجبة الممنوعة بنجاح']);
    }
}
