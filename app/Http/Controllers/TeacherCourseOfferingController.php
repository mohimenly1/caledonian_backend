<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use Illuminate\Http\Request;

class TeacherCourseOfferingController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'study_year_id' => 'required|exists:study_years,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $query = CourseOffering::with(['subject', 'schoolClass', 'section'])
            ->where('study_year_id', $request->study_year_id)
            ->where('class_id', $request->class_id);

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        return response()->json($query->get());
    }
}