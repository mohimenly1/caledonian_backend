<?php

namespace App\Http\Controllers;

use App\Models\EmployeeType;
use Illuminate\Http\Request;

class EmployeeTypeController extends Controller
{
    // Fetch all employee types
    public function index(Request $request)
    {
        $query = EmployeeType::query();
    
        // Apply search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%$search%");
        }
    
        // Paginate the result
        $employeeTypes = $query->paginate(10);
    
        return response()->json($employeeTypes);
    }

    
    public function EmployeeAllType()
    {
        $employeeTypes = EmployeeType::all();
        return response()->json($employeeTypes);
    }

    // Store a new employee type
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $employeeType = EmployeeType::create([
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Employee type created successfully', 'employeeType' => $employeeType]);
    }

    // Show a specific employee type
    public function show($id)
    {
        $employeeType = EmployeeType::findOrFail($id);
        return response()->json($employeeType);
    }

    // Update an employee type
    public function update(Request $request, $id)
    {
        $employeeType = EmployeeType::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $employeeType->update([
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Employee type updated successfully', 'employeeType' => $employeeType]);
    }

    // Delete an employee type
    public function destroy($id)
    {
        $employeeType = EmployeeType::findOrFail($id);
        $employeeType->delete();

        return response()->json(['message' => 'Employee type deleted successfully']);
    }
}
