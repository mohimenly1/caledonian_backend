<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Term;
use App\ApiResponse;

class TermController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $terms = Term::with('studyYear')->get();
        return $this->success($terms);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'study_year_id' => 'required|exists:study_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $term = Term::create($validated);
        return $this->success($term, 'Term created successfully');
    }

    public function show(Term $term)
    {
        return $this->success($term->load('studyYear'));
    }

    public function update(Request $request, Term $term)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'study_year_id' => 'required|exists:study_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $term->update($validated);
        return $this->success($term, 'Term updated successfully');
    }

    public function destroy(Term $term)
    {
        $term->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully',
        ], 200); // Explicit 200 status
    }
}
