<?php

namespace App\Http\Controllers;

use App\Models\SchedulePeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB; // <-- أضف هذا السطر في الأعلى

class SchedulePeriodController extends Controller
{

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'periods' => 'present|array',
            'periods.*.name' => 'required|string|max:255',
            'periods.*.start_time' => 'required|date_format:H:i:s',
            'periods.*.end_time' => 'required|date_format:H:i:s|after:periods.*.start_time',
            'periods.*.is_break' => 'required|boolean',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // 1. Delete all existing periods
                // Note: Add filtering here if periods are specific to a grade_level, e.g.,
                // SchedulePeriod::where('grade_level_id', $validated['grade_level_id'])->delete();
                SchedulePeriod::query()->delete();

                // 2. Insert the new set of periods
                foreach ($validated['periods'] as $periodData) {
                    SchedulePeriod::create($periodData);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred during the process.'], 500);
        }

        return response()->json(['message' => 'Schedule settings saved successfully.'], 200);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = SchedulePeriod::query()->orderBy('start_time');

        // Optional filtering by grade level if needed in the future
        if ($request->has('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // ⭐ التعديل هنا: قبول الثواني
            'start_time' => 'required|date_format:H:i:s',
            // ⭐ التعديل هنا: قبول الثواني
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'is_break' => 'sometimes|boolean',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
        ]);

        $period = SchedulePeriod::create($validated);
        return response()->json($period, 201);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SchedulePeriod  $schedulePeriod
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SchedulePeriod $schedulePeriod)
    {
        return response()->json($schedulePeriod);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SchedulePeriod  $schedulePeriod
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, SchedulePeriod $schedulePeriod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // ⭐ التعديل هنا: قبول الثواني
            'start_time' => 'required|date_format:H:i:s',
            // ⭐ التعديل هنا: قبول الثواني
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'is_break' => 'sometimes|boolean',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
        ]);

        $schedulePeriod->update($validated);
        return response()->json($schedulePeriod);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SchedulePeriod  $schedulePeriod
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SchedulePeriod $schedulePeriod)
    {
        // Optional: Check if the period is currently used in any schedule before deleting
        if ($schedulePeriod->classSchedules()->exists()) {
            return response()->json(['message' => 'Cannot delete this period because it is currently used in a schedule.'], 409);
        }

        $schedulePeriod->delete();

        return response()->json(null, 204);
    }
}
