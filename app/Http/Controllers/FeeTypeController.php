<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\FeeType;


class FeeTypeController extends Controller
{
    public function index()
    {
        return response()->json(FeeType::with('incomeAccount')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fee_types,name',
            'description' => 'nullable|string',
            'income_account_id' => 'required|exists:accounts,id',
        ]);

        $feeType = FeeType::create($validated);
        return response()->json($feeType, 201);
    }

    public function update(Request $request, FeeType $feeType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('fee_types')->ignore($feeType->id)],
            'description' => 'nullable|string',
            'income_account_id' => 'required|exists:accounts,id',
        ]);

        $feeType->update($validated);
        return response()->json($feeType);
    }

    public function destroy(FeeType $feeType)
    {
        // Prevent deletion if the fee type is used in any fee structure
        if ($feeType->feeStructures()->exists()) {
            return response()->json(['message' => 'Cannot delete this fee type because it is used in a fee structure.'], 422);
        }

        $feeType->delete();
        return response()->json(['message' => 'Fee type deleted successfully.']);
    }
}