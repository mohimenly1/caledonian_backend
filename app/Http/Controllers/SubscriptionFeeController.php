<?php

// app/Http/Controllers/SubscriptionFeeController.php

namespace App\Http\Controllers;

use App\Models\FinancialDocument;
use App\Models\Student;
use App\Models\SubscriptionFee;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Http\Request;

class SubscriptionFeeController extends Controller
{
    public function getFees($studentId)
    {
        // Fetch all subscription fees
        $subscriptionFees = SubscriptionFee::all();
        
        // Fetch the latest financial document for the student using the pivot table
        $financialDocument = FinancialDocument::whereHas('subscriptionFees', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })->latest()->first(); // Get the most recent financial document
     
        $studentFees = [];
        if ($financialDocument) {
            // Fetch the selected fees (ids) from the financial document's pivot table
            $studentFees = $financialDocument->subscriptionFees->where('student_id', $studentId)->pluck('id')->toArray();
        }
     
        return response()->json([
            'subscriptionFees' => $subscriptionFees,
            'selectedFees' => $studentFees,  // Return the selected fees
        ]);
    }
    
    
    
    public function index()
    {
   
        $fees = SubscriptionFee::all();
        return response()->json($fees);
    }

    public function storeSubscriptionFees(Request $request, Student $student)
    {
        // Validate subscription fees
        $validatedData = $request->validate([
            'subscriptionFees' => 'required|array',
            'subscriptionFees.*.subscription_fee_id' => 'required|exists:subscription_fees,id',
            'subscriptionFees.*.amount' => 'required|numeric',
            'treasury_id' => 'required|exists:treasuries,id',
        ]);

        // Attach subscription fees to the student
        $student->subscriptionFees()->sync($validatedData['subscriptionFees']);

        // Calculate total amount of subscription fees
        $totalAmount = array_reduce($validatedData['subscriptionFees'], function($carry, $fee) {
            return $carry + $fee['amount'];
        }, 0);

        // Get the selected treasury
        $treasury = Treasury::findOrFail($validatedData['treasury_id']);

        // Check if the treasury is manual
        if ($treasury->type !== 'manual') {
            return response()->json(['message' => 'Selected treasury is not a manual treasury.'], 400);
        }

        // Update the treasury balance
        $treasury->balance += $totalAmount;
        $treasury->save();

        // Log the treasury transaction
        TreasuryTransaction::create([
            'treasury_id' => $treasury->id,
            'transaction_type' => 'deposit',
            'amount' => $totalAmount,
            'description' => 'Subscription Fees Deposit',
            'related_id' => $student->id,
            'related_type' => 'student_subscription_fee',
        ]);

        return response()->json(['message' => 'Subscription fees saved and deposited successfully']);
    }



    public function storeSubscriptionFeesOk(Request $request, Student $student)
    {
        // Validate subscription fees
        $validatedData = $request->validate([
            'subscriptionFees' => 'required|array',
            'subscriptionFees.*.subscription_fee_id' => 'required|exists:subscription_fees,id',
            'subscriptionFees.*.amount' => 'required|numeric',
            'treasury_id' => 'required|exists:treasuries,id'
        ]);

        // Retrieve the selected treasury
        $treasury = Treasury::findOrFail($validatedData['treasury_id']);

        // Calculate the total amount of subscription fees
        $totalAmount = array_reduce($validatedData['subscriptionFees'], function($sum, $item) {
            return $sum + $item['amount'];
        }, 0);

        // Check if the treasury has enough balance
        if ($treasury->balance < $totalAmount) {
            return response()->json(['message' => 'Insufficient funds in the treasury.'], 400);
        }

        // Attach subscription fees to the student
        $student->subscriptionFees()->sync($validatedData['subscriptionFees']);

        // Update the treasury balance
        $treasury->balance -= $totalAmount;
        $treasury->save();

        // Create a treasury transaction record
        TreasuryTransaction::create([
            'treasury_id' => $treasury->id,
            'transaction_type' => 'subscription_fee',
            'amount' => $totalAmount,
            'description' => 'Subscription Fee Payment',
            'related_id' => $student->id,
            'related_type' => 'student_subscription_fee',
        ]);

        return response()->json(['message' => 'Subscription fees saved and treasury updated successfully']);
    }
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'category' => 'required|string',
            'sub_category' => 'nullable|string',
            'amount' => 'required|numeric',
        ]);

        $subscriptionFee = SubscriptionFee::findOrFail($id);
        $subscriptionFee->update($validatedData);

        return response()->json(['message' => 'Subscription fee updated successfully']);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category' => 'required|string',
            'sub_category' => 'nullable|string',
            'amount' => 'required|numeric',
        ]);

        $subscriptionFee = SubscriptionFee::create($validatedData);
        return response()->json(['message' => 'Subscription fee created successfully', 'data' => $subscriptionFee]);
    }

    public function destroy($id)
    {
        $subscriptionFee = SubscriptionFee::findOrFail($id);
        $subscriptionFee->delete();

        return response()->json(['message' => 'Subscription fee deleted successfully']);
    }
}
