<?php

namespace App\Http\Controllers;

use App\Models\DeductionPerHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeductionPerHourController extends Controller
{
    // Fetch all deductions
    public function index()
    {
        $deductions = DeductionPerHour::all();
        return response()->json($deductions);
    }

    // Store a new deduction
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deduction = DeductionPerHour::create($request->only('amount', 'description'));
        return response()->json(['message' => 'Deduction created successfully', 'data' => $deduction], 201);
    }

    // Update a deduction
    public function update(Request $request, $id)
    {
        $deduction = DeductionPerHour::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deduction->update($request->only('amount', 'description'));
        return response()->json(['message' => 'Deduction updated successfully', 'data' => $deduction]);
    }

    // Soft delete a deduction
    public function destroy($id)
    {
        $deduction = DeductionPerHour::findOrFail($id);
        $deduction->delete();
        return response()->json(['message' => 'Deduction deleted successfully']);
    }
}
