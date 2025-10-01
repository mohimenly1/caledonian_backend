<?php

namespace App\Http\Controllers;

use App\Models\AbsenceType;
use Illuminate\Http\Request;

class AbsenceTypeController extends Controller
{
    // Create new absence type
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $absenceType = AbsenceType::create($validatedData);

        return response()->json([
            'message' => 'Absence type created successfully',
            'absence_type' => $absenceType
        ], 201);
    }

    // Update absence type
    public function update(Request $request, $id)
    {
        $absenceType = AbsenceType::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $absenceType->update($validatedData);

        return response()->json([
            'message' => 'Absence type updated successfully',
            'absence_type' => $absenceType
        ]);
    }

    // Get all absence types
    public function index()
    {
        $absenceTypes = AbsenceType::all();
        return response()->json($absenceTypes);
    }

    // Delete absence type
    public function destroy($id)
    {
        $absenceType = AbsenceType::findOrFail($id);
        $absenceType->delete();

        return response()->json(['message' => 'Absence type deleted successfully']);
    }
}
