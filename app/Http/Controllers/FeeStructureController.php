<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use Illuminate\Http\Request;

class FeeStructureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'study_year_id' => 'required|exists:study_years,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
        ]);

        $query = FeeStructure::with(['feeType:id,name', 'gradeLevel:id,name'])
                             ->where('study_year_id', $request->study_year_id);

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        } else {
            // Get fees that are general for the whole study year (no grade level)
            $query->whereNull('grade_level_id');
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fee_type_id' => 'required|exists:fee_types,id',
            'study_year_id' => 'required|exists:study_years,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Use updateOrCreate to prevent duplicate fee structures for the same scope.
        $feeStructure = FeeStructure::updateOrCreate(
            [
                'study_year_id' => $validated['study_year_id'],
                'grade_level_id' => $validated['grade_level_id'] ?? null,
                'fee_type_id' => $validated['fee_type_id'],
            ],
            [
                'amount' => $validated['amount'],
            ]
        );

        return response()->json($feeStructure->load('feeType', 'gradeLevel'), 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeeStructure $feeStructure)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $feeStructure->update($validated);

        return response()->json($feeStructure->load('feeType', 'gradeLevel'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeeStructure $feeStructure)
    {
        // Add logic here to prevent deletion if this fee is part of any invoice.
        // For now, we allow deletion.
        $feeStructure->delete();
        
        return response()->json(['message' => 'Fee structure deleted successfully.']);
    }
}
