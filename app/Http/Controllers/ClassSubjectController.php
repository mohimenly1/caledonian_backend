<?php

// app/Http/Controllers/ClassSubjectController.php
namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Student;
use App\Models\ClassSectionSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassSubjectController extends Controller
{

    public function getClassSubjects(Request $request)
    {
        try {
            // Get the authenticated user
            $user = $request->user();
            
            // Find the student record for this user
            $student = Student::where('user_id', $user->id)
                ->with(['class', 'section'])
                ->firstOrFail();
            
            // Validate we have the required class_id
            if (!$student->class_id) {
                return response()->json([
                    'message' => 'Student is not assigned to any class'
                ], 400);
            }
            
            $query = ClassSectionSubject::where('class_id', $student->class_id)
                ->with(['subject']);
                
            // Add section filter if student has a section
            if ($student->section_id) {
                $query->where('section_id', $student->section_id);
            }
            
            $subjects = $query->get()
                ->pluck('subject') // This is the key change
                ->filter() // Remove null subjects
                ->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'description' => $subject->description,
                        'image_url' => $subject->image_url,
                    ];
                })
                ->values(); // Reset array keys
                
            return response()->json([
                'success' => true,
                'class_id' => $student->class_id,
                'section_id' => $student->section_id,
                'subjects' => $subjects
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subjects: ' . $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {
        $classes = ClassRoom::withCount(['subjects'])
            ->with(['subjects', 'sections' => function($query) {
                $query->withCount('subjects')
                      ->with('subjects');
            }])
            ->get()
            ->map(function($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'description' => $class->description,
                    'subjects_count' => $class->subjects_count,
                    'subjects' => $class->subjects,
                    'sections' => $class->sections->map(function($section) {
                        return [
                            'id' => $section->id,
                            'name' => $section->name,
                            'subjects_count' => $section->subjects_count,
                            'subjects' => $section->subjects
                        ];
                    })
                ];
            });

        $subjects = Subject::all();
        
        return response()->json([
            'classes' => $classes,
            'subjects' => $subjects
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id'
        ]);

        DB::transaction(function () use ($request) {
            $class = ClassRoom::find($request->class_id);
            
            if ($request->section_id) {
                $class->subjects()->syncWithoutDetaching(
                    collect($request->subject_ids)->mapWithKeys(function ($subjectId) use ($request) {
                        return [$subjectId => ['section_id' => $request->section_id]];
                    })
                );
            } else {
                $class->subjects()->syncWithoutDetaching($request->subject_ids);
            }
        });

        return response()->json(['message' => 'Subjects assigned successfully']);
    }

    public function show(ClassRoom $class)
    {
        return response()->json([
            'class' => $class->load(['subjects', 'sections.subjects'])
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_ids' => 'required|string' // Now accepting string
        ]);
    
        $subjectIds = explode(',', $request->subject_ids); // Convert back to array
        
        $class = ClassRoom::find($request->class_id);
        
        if ($request->section_id) {
            $class->subjects()
                ->wherePivot('section_id', $request->section_id)
                ->detach($subjectIds);
        } else {
            $class->subjects()->detach($subjectIds);
        }
    
        return response()->json(['message' => 'Subjects unassigned successfully']);
    }
}