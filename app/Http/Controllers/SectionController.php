<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\TeacherType;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $query = Section::with(['class.studyYear:id,name']);

        // Filter by specific class
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Filter by study year (through the class relationship)
        if ($request->filled('study_year_id')) {
            $query->whereHas('class', function ($q) use ($request) {
                $q->where('study_year_id', $request->study_year_id);
            });
        }

        $sections = $query->orderBy('created_at', 'desc')->get();

        return response()->json($sections);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
        ]);

        $section = Section::create($request->only('name', 'class_id'));

        if ($request->has('subject_ids')) {
            $section->subjects()->attach($request->subject_ids);
        }

        return response()->json($section, 201);
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
        ]);

        $section->update($request->only('name', 'class_id'));

   
        return response()->json($section);
    }

    public function destroy(Section $section)
    {
        $section->delete();
        return response()->noContent();
    }


    public function getSubjects($id)
    {
        $section = Section::findOrFail($id);
        $subjects = $section->subjects;
        return response()->json($subjects);
    }

    public function getSubjectsByClassAndSection(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');

        $subjects = TeacherType::whereHas('sections', function($query) use ($classId, $sectionId) {
            $query->where('class_id', $classId)->where('section_id', $sectionId);
        })->get();

        return response()->json($subjects);
    }
}
