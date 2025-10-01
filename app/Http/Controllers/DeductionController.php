<?php

namespace App\Http\Controllers;

use App\Models\Deduction;
use Illuminate\Http\Request;

class DeductionController extends Controller
{

    public function index(Request $request)
    {
        $employeeId = $request->get('employee_id');
    
        // If employee_id is present, filter by employee_id, otherwise fetch all records
        $deductions = Deduction::when($employeeId, function ($query, $employeeId) {
            return $query->where('employee_id', $employeeId);
        })->with(['deductionType', 'employee'])->get();
    
        return response()->json($deductions);
    }
    

    // Create new deduction
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric',
            'reason' => 'required|string',
            'date' => 'required|date',
            'deduction_type_id' => 'required|exists:deduction_types,id',
        ]);

        $deduction = Deduction::create([
            'employee_id' => $request->employee_id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'date' => $request->date,
            'deduction_type_id' => $request->deduction_type_id,
        ]);

        return response()->json(['message' => 'Deduction recorded successfully', 'deduction' => $deduction], 201);
    }
    // Update deduction
    public function update(Request $request, $id)
    {
        $deduction = Deduction::findOrFail($id);

        $validatedData = $request->validate([
            'amount' => 'required|numeric',
            'reason' => 'required|string',
            'deduction_type_id' => 'required|exists:deduction_types,id',
            'date' => 'required|date',
        ]);

        $deduction->update($validatedData);

        return response()->json([
            'message' => 'Deduction updated successfully',
            'deduction' => $deduction
        ]);
    }

    // Get all deductions for an employee
    public function show($employee_id)
    {
        $deductions = Deduction::where('employee_id', $employee_id)->with('deductionType')->get();

        return response()->json($deductions);
    }

    // Delete deduction
    public function destroy($id)
    {
        $deduction = Deduction::findOrFail($id);
        $deduction->delete();

        return response()->json(['message' => 'Deduction deleted successfully']);
    }
}
