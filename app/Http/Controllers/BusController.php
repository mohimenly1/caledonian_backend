<?php
// app/Http/Controllers/Api/BusController.php (For Admin Vue Dashboard)

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\User; // To fetch drivers
use Illuminate\Http\Request;

class BusController extends Controller
{
    /**
     * Display a listing of the buses.
     */
    public function index()
    {
        return response()->json(Bus::with('driver:id,name')->withCount('students')->get());
    }

    /**
     * Store a newly created bus in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_number' => 'required|string|max:255',
            'plate_number' => 'required|string|max:255|unique:buses,plate_number',
            'driver_id' => 'nullable|exists:users,id',
        ]);

        $bus = Bus::create($validated);
        return response()->json($bus->load('driver:id,name'), 201);
    }

    /**
     * Display the specified bus with its students.
     */
    public function show(Bus $bus)
    {
        return response()->json($bus->load('students:id,name'));
    }

    /**
     * Update the specified bus in storage.
     */
    public function update(Request $request, Bus $bus)
    {
        $validated = $request->validate([
            'bus_number' => 'required|string|max:255',
            'plate_number' => 'required|string|max:255|unique:buses,plate_number,' . $bus->id,
            'driver_id' => 'nullable|exists:users,id',
        ]);

        $bus->update($validated);
        return response()->json($bus->load('driver:id,name'));
    }

    /**
     * Remove the specified bus from storage.
     */
    public function destroy(Bus $bus)
    {
        // Prevent deletion if students are assigned
        if ($bus->students()->count() > 0) {
            return response()->json(['message' => 'Cannot delete a bus with assigned students.'], 422);
        }
        $bus->delete();
        return response()->json(['message' => 'Bus deleted successfully.']);
    }

    /**
     * Assign a list of students to a bus.
     */
    public function assignStudents(Request $request, Bus $bus)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $bus->students()->sync($validated['student_ids']);

        return response()->json(['message' => 'Students assigned successfully.']);
    }

    /**
     * Get a list of available drivers.
     */
    public function getDrivers()
    {
        $drivers = User::where('user_type', 'driver')->get(['id', 'name']);
        return response()->json($drivers);
    }
    public function getAvailableBuses()
{
    // Returns a simple list of buses for dropdowns
    return response()->json(Bus::get(['id', 'bus_number']));
}
}
