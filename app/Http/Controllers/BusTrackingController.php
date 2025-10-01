<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;

class BusTrackingController extends Controller
{
    // Called by the Driver's App
    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $driver = Auth::user();
        $bus = Bus::where('driver_id', $driver->id)->firstOrFail();

        $bus->locations()->create([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'timestamp' => now(),
        ]);

        return response()->json(['message' => 'Location updated successfully.']);
    }

    // Called by the Parent's App
    public function getBusLocation(Request $request)
    {
        // 1. Validate that student_id is provided and exists.
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $studentId = $validated['student_id'];
        $student = Student::findOrFail($studentId);
        $parent = Auth::user()->parentProfile;

        // 2. Security Check: Ensure the logged-in parent is the parent of the requested student.
        if ($student->parent_id !== $parent->id) {
            return response()->json(['message' => 'Unauthorized. This is not your child.'], 403);
        }

        // 3. Check if the student is assigned to a bus and get the first one.
        // --- THE FIX IS HERE: We get the first bus from the relationship collection ---
        $bus = $student->bus()->first();

        if (!$bus) {
            return response()->json(['message' => 'This student is not currently assigned to a bus.'], 404);
        }

        // 4. Load the necessary bus details.
        $bus->load('latestLocation', 'driver:id,name');

        // 5. Define school location.
        $schoolLocation = [
            'latitude' => 32.8605997362668,
            'longitude' => 13.123245729780056,
            'name' => 'Caledonian International School'
        ];

        // 6. Return the complete response for the Flutter app.
        return response()->json([
            'student_name' => $student->name,
            'bus' => [
                'bus_number' => $bus->bus_number,
                'driver_name' => optional($bus->driver)->name,
            ],
            'current_location' => $bus->latestLocation,
            'school_location' => $schoolLocation,
        ]);
    }
}