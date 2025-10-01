<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Employee;
use App\Services\AbsenceCalculator;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->get('employee_id');
    
        // If employee_id is present, filter by employee_id, otherwise fetch all records
        $absences = Absence::when($employeeId, function ($query, $employeeId) {
            return $query->where('employee_id', $employeeId);
        })->with(['employee', 'absenceType'])->get();
    
        return response()->json($absences);
    }
    

public function store(Request $request)
{
    $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'date' => 'required|date',
        'absence_type_id' => 'required|exists:absence_types,id',
    ]);

    $absence = Absence::create([
        'employee_id' => $request->employee_id,
        'date' => $request->date,
        'absence_type_id' => $request->absence_type_id,
    ]);

    return response()->json(['message' => 'Absence recorded successfully', 'absence' => $absence], 201);
}

    // Update employee absence
    public function update(Request $request, $id)
    {
        $absence = Absence::findOrFail($id);

        $validatedData = $request->validate([
            'date' => 'required|date',
            'absence_type_id' => 'required|exists:absence_types,id',
        ]);

        $absence->update($validatedData);

        return response()->json([
            'message' => 'Absence updated successfully',
            'absence' => $absence
        ]);
    }

    // Get all absences for an employee
    public function show($employee_id)
    {
        $absences = Absence::where('employee_id', $employee_id)->get();

        return response()->json($absences);
    }

    // Delete absence
    public function destroy($id)
    {
        $absence = Absence::findOrFail($id);
        $absence->delete();

        return response()->json(['message' => 'Absence deleted successfully']);
    }
    protected $absenceCalculator;

    public function __construct(AbsenceCalculator $absenceCalculator)
    {
        $this->absenceCalculator = $absenceCalculator;
    }

    // public function calculateAbsences(Request $request, $employeeId)
    // {
    //     $employee = Employee::findOrFail($employeeId);
    //     $this->absenceCalculator->calculateMonthlyAbsencesAndSalary($employee);

    //     return response()->json(['message' => 'Absences calculated and salary adjusted successfully.']);
    // }
}
