<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\ParentWallet;
use App\Models\Purchase;
use App\Models\Student;
use App\Models\StudentPurchaseCeiling;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
 // Make a purchase
 public function store(Request $request)
 {
     $request->validate([
         'student_id' => 'required|exists:students,id',
         'meal_id' => 'required|exists:meals,id',
         'parent_id' => 'required|exists:parents,id',
         'amount' => 'required|numeric',
     ]);

     // Check if student has a purchase ceiling limit
     $ceiling = StudentPurchaseCeiling::where('student_id', $request->student_id)->first();
     if ($ceiling && $request->amount > $ceiling->purchase_ceiling) {
         return response()->json(['message' => 'Purchase exceeds the allowed ceiling for this student!'], 403);
     }

     // Check parent wallet balance
     $wallet = ParentWallet::where('parent_id', $request->parent_id)->first();
     if (!$wallet || $wallet->balance < $request->amount) {
         return response()->json(['message' => 'Insufficient balance in parent\'s wallet!'], 403);
     }

     // Deduct the amount from the wallet
     $wallet->balance -= $request->amount;
     $wallet->save();

     // Create a purchase
     $purchase = Purchase::create([
         'student_id' => $request->student_id,
         'meal_id' => $request->meal_id,
         'parent_id' => $request->parent_id,
         'amount' => $request->amount,
         'status' => 'done'
     ]);

     return response()->json([
         'message' => 'Purchase completed successfully!',
         'purchase' => $purchase
     ], 201);
 }

 // Cancel a purchase
 public function cancel(Purchase $purchase)
 {
     if ($purchase->status !== 'done') {
         return response()->json(['message' => 'Only completed purchases can be cancelled!'], 403);
     }

     // Refund the parent wallet
     $wallet = ParentWallet::where('parent_id', $purchase->parent_id)->first();
     if ($wallet) {
         $wallet->balance += $purchase->amount;
         $wallet->save();
     }

     // Update purchase status
     $purchase->status = 'cancelled';
     $purchase->save();

     return response()->json([
         'message' => 'Purchase cancelled successfully!',
         'purchase' => $purchase
     ]);
 }

 // Edit a purchase
 public function update(Request $request, Purchase $purchase)
 {
     $request->validate([
         'amount' => 'required|numeric',
     ]);

     // Check parent wallet balance
     $wallet = ParentWallet::where('parent_id', $purchase->parent_id)->first();
     $new_amount = $request->amount;

     // Handle the difference in amounts for edit
     if ($new_amount > $purchase->amount) {
         $difference = $new_amount - $purchase->amount;

         if ($wallet->balance < $difference) {
             return response()->json(['message' => 'Insufficient balance in parent\'s wallet for this update!'], 403);
         }

         $wallet->balance -= $difference;
     } else {
         $difference = $purchase->amount - $new_amount;
         $wallet->balance += $difference;
     }

     $wallet->save();

     // Update purchase amount and status
     $purchase->update([
         'amount' => $new_amount,
         'status' => 'edited'
     ]);

     return response()->json([
         'message' => 'Purchase updated successfully!',
         'purchase' => $purchase
     ]);
 }

 // List all purchases for a student
 public function studentPurchases($student_id)
 {
     $purchases = Purchase::where('student_id', $student_id)->with('meal')->get();
     return response()->json($purchases);
 }

}
