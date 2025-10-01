<?php

namespace App\Http\Controllers;

use App\Models\Vacation;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    // Create new vacation
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'vacation_type' => 'required|string',
        ]);

        $vacation = Vacation::create($validatedData);

        return response()->json([
            'message' => 'Vacation created successfully',
            'vacation' => $vacation
        ], 201);
    }

    // Update vacation
    public function update(Request $request, $id)
    {
        $vacation = Vacation::findOrFail($id);

        $validatedData = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'vacation_type' => 'required|string',
        ]);

        $vacation->update($validatedData);

        return response()->json([
            'message' => 'Vacation updated successfully',
            'vacation' => $vacation
        ]);
    }

    // Get vacations for an employee
    public function show($employee_id)
    {
        $vacations = Vacation::where('employee_id', $employee_id)->get();

        return response()->json($vacations);
    }

    // Delete vacation
    public function destroy($id)
    {
        $vacation = Vacation::findOrFail($id);
        $vacation->delete();

        return response()->json(['message' => 'Vacation deleted successfully']);
    }
}
