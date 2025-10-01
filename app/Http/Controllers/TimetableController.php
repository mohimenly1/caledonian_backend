<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Services\TimetableService;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimetableController extends Controller
{
 
    // Get timetable for a class or section
// In TimetableController, update the index method:

public function getTeacher(Request $request)
{
    $query = Employee::query();
    
    if ($request->has('is_teacher')) {
        $query->where('is_teacher', $request->boolean('is_teacher'));
    }
    
    return response()->json($query->get(['id', 'name', 'user_id']));
}
public function index(Request $request)
{
    $request->validate([
        'class_id' => 'nullable|exists:classes,id',
        'section_id' => 'nullable|exists:sections,id',
        'date' => 'nullable|date',
        // 'week' => 'nullable|boolean',
    ]);

    $service = new TimetableService();
    $date = $request->date ? Carbon::parse($request->date) : now();

    if ($request->boolean('week')) {
        $data = $service->getTimetableForWeek(
            $date,
            $request->class_id,
            $request->section_id
        );
    } else {
        $data = $service->getTimetableForDate(
            $date,
            $request->class_id,
            $request->section_id
        );
    }

    return response()->json($data);
}

    public function store(Request $request)
    {
        $request->validate([
            'entries' => 'required|array',
            'entries.*.class_id' => 'nullable|exists:classes,id',
            'entries.*.section_id' => 'nullable|exists:sections,id',
            'entries.*.subject_id' => 'required|exists:subjects,id',
            'entries.*.teacher_id' => 'nullable|exists:users,id',
            'entries.*.day_of_week' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'entries.*.start_time' => 'required|date_format:H:i',
            'entries.*.end_time' => 'required|date_format:H:i|after:entries.*.start_time',
            'entries.*.is_recurring' => 'boolean',
            'entries.*.specific_date' => 'nullable|date',
            'entries.*.is_holiday' => 'boolean',
            'entries.*.notes' => 'nullable|string',
        ]);

        $createdEntries = [];
        
        foreach ($request->entries as $entry) {
            $timetable = Timetable::create([
                'class_id' => $entry['class_id'] ?? null,
                'section_id' => $entry['section_id'] ?? null,
                'subject_id' => $entry['subject_id'],
                'teacher_id' => $entry['teacher_id'] ?? null,
                'day_of_week' => $entry['day_of_week'],
                'start_time' => $entry['start_time'],
                'end_time' => $entry['end_time'],
                'duration' => Carbon::parse($entry['end_time'])->diffInMinutes(Carbon::parse($entry['start_time'])),
                'is_recurring' => $entry['is_recurring'] ?? true,
                'specific_date' => $entry['specific_date'] ?? null,
                'is_holiday' => $entry['is_holiday'] ?? false,
                'notes' => $entry['notes'] ?? null,
            ]);

            $createdEntries[] = $this->formatTimetableItem($timetable);
        }

        return response()->json([
            'message' => 'Timetable entries created successfully',
            'entries' => $createdEntries,
        ], 201);
    }

    // Helper method to format timetable items
    private function formatTimetableItem(Timetable $item)
    {
    
        return [
            'id' => $item->id,
            'class_id' => $item->class_id,
            'class_name' => $item->class?->name,
            'section_id' => $item->section_id,
            'section_name' => $item->section?->name,
            'subject_id' => $item->subject_id,
            'subject_name' => $item->subject?->name,
            'subject_code' => $item->subject?->code,
            'teacher_id' => $item->teacher_id,
            'teacher_name' => $item->teacher?->name, // Changed from user to teacher
            'day_of_week' => $item->day_of_week,
            'start_time' => $item->start_time->format('H:i'),
            'end_time' => $item->end_time->format('H:i'),
            'duration' => $item->duration,
            'is_recurring' => $item->is_recurring,
            'specific_date' => $item->specific_date?->format('Y-m-d'),
            'is_holiday' => $item->is_holiday,
            'notes' => $item->notes,
        ];
    }

    public function update(Request $request, Timetable $timetable)
{
    $request->validate([
        'class_id' => 'nullable|exists:classes,id',
        'section_id' => 'nullable|exists:sections,id',
        'subject_id' => 'required|exists:subjects,id',
        'teacher_id' => 'nullable|exists:users,id',
        'day_of_week' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',
        'is_recurring' => 'boolean',
        'specific_date' => 'nullable|date',
        'is_holiday' => 'boolean',
        'notes' => 'nullable|string',
    ]);

    $timetable->update([
        'class_id' => $request->class_id,
        'section_id' => $request->section_id,
        'subject_id' => $request->subject_id,
        'teacher_id' => $request->teacher_id,
        'day_of_week' => $request->day_of_week,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'duration' => Carbon::parse($request->end_time)->diffInMinutes(Carbon::parse($request->start_time)),
        'is_recurring' => $request->is_recurring ?? true,
        'specific_date' => $request->specific_date,
        'is_holiday' => $request->is_holiday ?? false,
        'notes' => $request->notes,
    ]);

    return response()->json([
        'message' => 'Timetable entry updated successfully',
        'entry' => $this->formatTimetableItem($timetable),
    ]);
}

public function destroy(Timetable $timetable)
{
    $timetable->delete();

    return response()->json([
        'message' => 'Timetable entry deleted successfully'
    ]);
}
}