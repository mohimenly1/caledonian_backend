<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use Illuminate\Http\Request;

class MealController extends Controller
{
    // Add a new meal
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:meal_categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image file
        ]);

        $imagePath = null;

        // Check if an image was uploaded
        if ($request->hasFile('image')) {
            // Store the image in the 'public' disk (public/storage folder)
            $imagePath = $request->file('image')->store('meals', 'public');
        }

        // Create the meal
        $meal = Meal::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'price' => $request->price,
            'image' => $imagePath, // Save the image path in the database
        ]);

        return response()->json([
            'message' => 'Meal added successfully!',
            'meal' => $meal
        ], 201);
    }

    // Update a meal
    public function update(Request $request, Meal $meal)
    {
        $request->validate([
            'category_id' => 'required|exists:meal_categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'image' => 'nullable|string',
        ]);

        $meal->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'price' => $request->price,
            'image' => $request->image,
        ]);

        return response()->json([
            'message' => 'Meal updated successfully!',
            'meal' => $meal
        ]);
    }

    // Delete a meal
    public function destroy(Meal $meal)
    {
        $meal->delete();

        return response()->json([
            'message' => 'Meal deleted successfully!'
        ]);
    }

    // List all meals with optional category filtering
    public function index(Request $request)
    {
        $query = Meal::with('category');

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name if provided
        if ($request->has('search')) {

            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $meals = $query->get();
        return response()->json($meals);
    }

    // Get a single meal
    public function show(Meal $meal)
    {
        return response()->json($meal);
    }
}
