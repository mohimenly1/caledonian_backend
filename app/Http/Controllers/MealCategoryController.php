<?php

namespace App\Http\Controllers;

use App\Models\MealCategory;
use Illuminate\Http\Request;

class MealCategoryController extends Controller
{

    // Add a new meal category
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = MealCategory::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Meal category added successfully!',
            'category' => $category
        ], 201);
    }

    // Update an existing meal category
    public function update(Request $request, MealCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Meal category updated successfully!',
            'category' => $category
        ]);
    }

    // Delete a meal category
    public function destroy(MealCategory $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Meal category deleted successfully!'
        ]);
    }

    // List all categories
    public function index()
    {
        $categories = MealCategory::all();
        return response()->json($categories);
    }
}
