<?php

// app/Services/TimetableService.php
namespace App\Services;

use App\Models\Timetable;
use App\Models\Holiday;
use Carbon\Carbon;

class TimetableService
{
    public function getTimetableForDate($date, $classId = null, $sectionId = null)
    {
        $date = Carbon::parse($date);
        
        // Check if it's a holiday
        if (Holiday::onDate($date)->exists()) {
            $holiday = Holiday::onDate($date)->first();
            return [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'is_holiday' => true,
                'holiday_name' => $holiday->name,
                'periods' => []
            ];
        }
    
        $query = Timetable::with(['class', 'section', 'subject', 'teacher'])
            ->forDate($date);
    
        if ($classId) {
            $query->forClass($classId);
        }
    
        if ($sectionId) {
            $query->forSection($sectionId);
        }
    
        $periods = $query->orderBy('start_time')
            ->get()
            ->map(function ($item) {
                return $this->formatTimetableItem($item);
            });
    
        return [
            'date' => $date->format('Y-m-d'),
            'day_name' => $date->format('l'),
            'is_holiday' => false,
            'periods' => $periods
        ];
    }

    public function getTimetableForWeek($startDate, $classId = null, $sectionId = null)
    {
        $startDate = Carbon::parse($startDate)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
    
        $holidays = Holiday::onDateRange($startDate, $endDate)->get();
        $timetable = [];
    
        for ($day = $startDate; $day <= $endDate; $day->addDay()) {
            $isHoliday = $holidays->contains(function ($holiday) use ($day) {
                return $day->between($holiday->start_date, $holiday->end_date);
            });
    
            $dayTimetable = $isHoliday ? [] : $this->getTimetableForDate($day, $classId, $sectionId)['periods'];
    
            $timetable[] = [
                'date' => $day->format('Y-m-d'),
                'day_name' => $day->format('l'),
                'is_holiday' => $isHoliday,
                'holiday_name' => $isHoliday ? $holidays->firstWhere(
                    fn($h) => $day->between($h->start_date, $h->end_date)
                )->name : null,
                'periods' => $dayTimetable,
            ];
        }
    
        return [
            'week_start' => $startDate->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'timetable' => $timetable,
        ];
    }

    private function formatTimetableItem($item)
    {
        // dd($item);
        
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
}