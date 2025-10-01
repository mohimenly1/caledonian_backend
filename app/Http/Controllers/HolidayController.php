<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    /**
     * Display a listing of holidays.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'upcoming' => 'nullable|boolean',
            'active_only' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        $query = Holiday::query();
    
        // Filter by year if provided
        if ($request->has('year')) {
            $query->where(function($q) use ($request) {
                $q->whereYear('start_date', '<=', $request->year)
                  ->whereYear('end_date', '>=', $request->year);
            });
        }
    
        // Filter by month if provided
        if ($request->has('month')) {
            $query->where(function($q) use ($request) {
                $q->whereMonth('start_date', '<=', $request->month)
                  ->whereMonth('end_date', '>=', $request->month);
            });
        }
    
        // Show only upcoming holidays
        if ($request->boolean('upcoming')) {
            $query->where('end_date', '>=', Carbon::today())
                  ->orderBy('start_date');
        }
    
        // Show only active holidays (those that include today)
        if ($request->boolean('active_only')) {
            $today = Carbon::today();
            $query->where('start_date', '<=', $today)
                  ->where('end_date', '>=', $today);
        }
    
        // Pagination
        $limit = $request->input('limit', 15);
        $holidays = $query->orderBy('start_date')
                          ->paginate($limit);
    
        // Transform the collection items
        $transformed = $holidays->getCollection()->map(function ($holiday) {
            return $this->formatHoliday($holiday);
        });
    
        // Set the transformed collection back to the paginator
        $holidays->setCollection($transformed);
    
        return response()->json([
            'data' => $holidays->items(),
            'meta' => [
                'current_page' => $holidays->currentPage(),
                'per_page' => $holidays->perPage(),
                'total' => $holidays->total(),
                'last_page' => $holidays->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created holiday.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
// In store and update methods
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'start_date' => [
        'required',
        'date',
        function ($attribute, $value, $fail) {
            if (Carbon::parse($value)->isBefore(now()->startOfYear()->subYears(5))) {
                $fail('Start date cannot be more than 5 years in the past.');
            }
        }
    ],
    'end_date' => [
        'required',
        'date',
        'after_or_equal:start_date',
        function ($attribute, $value, $fail) use ($request) {
            if (Carbon::parse($value)->diffInDays(Carbon::parse($request->start_date)) > 30) {
                $fail('Holiday duration cannot exceed 30 days.');
            }
        }
    ],
    'repeats_annually' => 'boolean',
    'description' => 'nullable|string|max:1000',
]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check for overlapping holidays
        $overlapping = Holiday::where(function($query) use ($request) {
            $query->where(function($q) use ($request) {
                $q->where('start_date', '<=', $request->end_date)
                  ->where('end_date', '>=', $request->start_date);
            })->orWhere('repeats_annually', true)
              ->whereRaw('MONTH(start_date) = ?', [Carbon::parse($request->start_date)->month])
              ->whereRaw('DAY(start_date) <= ?', [Carbon::parse($request->end_date)->day])
              ->whereRaw('MONTH(end_date) = ?', [Carbon::parse($request->start_date)->month])
              ->whereRaw('DAY(end_date) >= ?', [Carbon::parse($request->start_date)->day]);
        })->exists();

        if ($overlapping) {
            return response()->json([
                'message' => 'This holiday overlaps with an existing holiday',
            ], 409);
        }

        $holiday = Holiday::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'repeats_annually' => $request->boolean('repeats_annually'),
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Holiday created successfully',
            'data' => $this->formatHoliday($holiday),
        ], 201);
    }

    /**
     * Display the specified holiday.
     *
     * @param Holiday $holiday
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Holiday $holiday)
    {
        return response()->json([
            'data' => $this->formatHoliday($holiday),
        ]);
    }

    /**
     * Update the specified holiday.
     *
     * @param Request $request
     * @param Holiday $holiday
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Holiday $holiday)
    {
// In store and update methods
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'start_date' => [
        'required',
        'date',
        function ($attribute, $value, $fail) {
            if (Carbon::parse($value)->isBefore(now()->startOfYear()->subYears(5))) {
                $fail('Start date cannot be more than 5 years in the past.');
            }
        }
    ],
    'end_date' => [
        'required',
        'date',
        'after_or_equal:start_date',
        function ($attribute, $value, $fail) use ($request) {
            if (Carbon::parse($value)->diffInDays(Carbon::parse($request->start_date)) > 30) {
                $fail('Holiday duration cannot exceed 30 days.');
            }
        }
    ],
    'repeats_annually' => 'boolean',
    'description' => 'nullable|string|max:1000',
]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check for overlapping holidays (excluding current holiday)
        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->start_date ?? $holiday->start_date;
            $endDate = $request->end_date ?? $holiday->end_date;

            $overlapping = Holiday::where('id', '!=', $holiday->id)
                ->where(function($query) use ($startDate, $endDate, $holiday) {
                    $query->where(function($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $endDate)
                          ->where('end_date', '>=', $startDate);
                    })->orWhere(function($q) use ($startDate, $endDate) {
                        $q->where('repeats_annually', true)
                          ->whereRaw('MONTH(start_date) = ?', [Carbon::parse($startDate)->month])
                          ->whereRaw('DAY(start_date) <= ?', [Carbon::parse($endDate)->day])
                          ->whereRaw('MONTH(end_date) = ?', [Carbon::parse($startDate)->month])
                          ->whereRaw('DAY(end_date) >= ?', [Carbon::parse($startDate)->day]);
                    });
                })->exists();

            if ($overlapping) {
                return response()->json([
                    'message' => 'This holiday would overlap with an existing holiday',
                ], 409);
            }
        }

        $holiday->update($request->only([
            'name', 'start_date', 'end_date', 'repeats_annually', 'description'
        ]));

        return response()->json([
            'message' => 'Holiday updated successfully',
            'data' => $this->formatHoliday($holiday),
        ]);
    }

    /**
     * Remove the specified holiday.
     *
     * @param Holiday $holiday
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json([
            'message' => 'Holiday deleted successfully',
        ]);
    }

    /**
     * Check if a specific date is a holiday.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $date = Carbon::parse($request->date);
        $holiday = Holiday::onDate($date)->first();

        if ($holiday) {
            return response()->json([
                'is_holiday' => true,
                'holiday' => $this->formatHoliday($holiday),
            ]);
        }

        return response()->json([
            'is_holiday' => false,
        ]);
    }

    /**
     * Format holiday data for consistent API responses.
     *
     * @param Holiday $holiday
     * @return array
     */
    protected function formatHoliday(Holiday $holiday)
    {
        return [
            'id' => $holiday->id,
            'name' => $holiday->name,
            'start_date' => $holiday->start_date->format('Y-m-d'),
            'end_date' => $holiday->end_date->format('Y-m-d'),
            'start_date_formatted' => $holiday->start_date->format('D, M j, Y'),
            'end_date_formatted' => $holiday->end_date->format('D, M j, Y'),
            'repeats_annually' => $holiday->repeats_annually,
            'description' => $holiday->description,
            'duration_days' => $holiday->start_date->diffInDays($holiday->end_date) + 1,
            'is_current' => $holiday->start_date <= now() && $holiday->end_date >= now(),
            'created_at' => $holiday->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $holiday->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    // Create a service method for better reuse
protected function checkForOverlappingHolidays($startDate, $endDate, $repeatsAnnually, $excludeId = null)
{
    $query = Holiday::when($excludeId, function ($q) use ($excludeId) {
        $q->where('id', '!=', $excludeId);
    });

    return $query->where(function($q) use ($startDate, $endDate, $repeatsAnnually) {
        // Check for date range overlaps
        $q->where(function($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
              ->where('end_date', '>=', $startDate);
        });

        // Check for annual recurring overlaps if applicable
        if ($repeatsAnnually) {
            $startMonth = Carbon::parse($startDate)->month;
            $startDay = Carbon::parse($startDate)->day;
            $endDay = Carbon::parse($endDate)->day;

            $q->orWhere(function($q) use ($startMonth, $startDay, $endDay) {
                $q->where('repeats_annually', true)
                  ->whereMonth('start_date', $startMonth)
                  ->whereDay('start_date', '<=', $endDay)
                  ->whereMonth('end_date', $startMonth)
                  ->whereDay('end_date', '>=', $startDay);
            });
        }
    })->exists();
}

/**
 * Get holidays for a specific academic year
 */
public function forAcademicYear(Request $request)
{
    $validator = Validator::make($request->all(), [
        'year' => 'required|integer|min:2000|max:2100',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $start = Carbon::create($request->year, 9, 1); // September 1st
    $end = Carbon::create($request->year + 1, 8, 31); // August 31st next year

    $holidays = Holiday::where(function($q) use ($start, $end) {
        $q->whereBetween('start_date', [$start, $end])
          ->orWhereBetween('end_date', [$start, $end])
          ->orWhere(function($q) use ($start, $end) {
              $q->where('start_date', '<', $start)
                ->where('end_date', '>', $end);
          });
    })->orderBy('start_date')->get();

    return response()->json([
        'data' => $holidays->map([$this, 'formatHoliday'])
    ]);
}
}