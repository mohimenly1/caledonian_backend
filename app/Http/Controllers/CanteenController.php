<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Meal;
use App\Models\StudentPurchaseCeiling;
use App\Models\StudentRestrictedMeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CanteenController extends Controller
{
    /**
     * --- النسخة المصححة والنهائية ---
     * Get all canteen-related data for a student.
     */
    public function getStudentCanteenProfile(Student $student)
    {
        $student->load('purchaseCeiling', 'parent.wallet');

        $purchaseLimit = $student->purchaseCeiling->purchase_ceiling ?? null;
        
        // --- التعديل الرئيسي هنا: تحديد الجدول بشكل صريح ---
        $restrictedMealIds = $student->restrictedMeals()->pluck('meals.id');

        $allMeals = Meal::with('category')->get();
        
        $purchases = $student->kitchen_bills()
                             ->with(['items.meal:id,name,price'])
                             ->orderBy('created_at', 'desc')
                             ->get();
        
        $walletBalance = $student->parent->wallet->balance ?? null;

        return response()->json([
            'purchase_limit' => $purchaseLimit,
            'restricted_meal_ids' => $restrictedMealIds,
            'all_meals' => $allMeals,
            'purchase_history' => $purchases,
            'parent_wallet_balance' => $walletBalance,
        ]);
    }

    /**
     * Update the daily purchase limit for a student.
     */
    public function updatePurchaseLimit(Request $request, Student $student)
    {
        $validated = $request->validate(['limit' => 'required|numeric|min:0']);
        $newLimit = $validated['limit'];
    
        // ✨ 1. استخدام transaction لضمان تنفيذ العمليتين معاً ✨
        // إذا فشلت إحدى العمليات، يتم التراجع عن الأخرى تلقائياً
        DB::transaction(function () use ($student, $newLimit) {
            
            // العملية الأولى: تحديث جدول سقف المشتريات (الكود الحالي)
            StudentPurchaseCeiling::updateOrCreate(
                ['student_id' => $student->id],
                ['purchase_ceiling' => $newLimit]
            );
    
            // ✨ 2. العملية الثانية: تحديث جدول الطالب نفسه ✨
            $student->daily_allowed_purchase_value = $newLimit;
            $student->save();
    
        });
    
        return response()->json(['message' => 'Purchase limit updated successfully.']);
    }

    /**
     * Add a meal to a student's restricted list.
     */
    public function addRestrictedMeal(Request $request, Student $student)
    {
        $validated = $request->validate(['meal_id' => 'required|exists:meals,id']);
        
        // Use createOrFirst to avoid duplicates
        StudentRestrictedMeal::firstOrCreate([
            'student_id' => $student->id,
            'meal_id' => $validated['meal_id']
        ]);

        return response()->json(['message' => 'Meal restriction added successfully.']);
    }

    /**
     * Remove a meal from a student's restricted list.
     */
    public function removeRestrictedMeal(Request $request, Student $student)
    {
        $validated = $request->validate(['meal_id' => 'required|exists:meals,id']);

        StudentRestrictedMeal::where('student_id', $student->id)
            ->where('meal_id', $validated['meal_id'])
            ->delete();

        return response()->json(['message' => 'Meal restriction removed successfully.']);
    }
}
