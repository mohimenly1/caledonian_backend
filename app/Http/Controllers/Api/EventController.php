<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // Fetch all events for the authenticated user
    public function index()
    {
        $events = Event::all();
        return response()->json($events);
    }

    /**
     * Store a newly created resource in storage.
     */
   // Create a new event
   public function store(Request $request)
   {
       $request->validate([
           'title' => 'required|string|max:255',
           'description' => 'nullable|string',
           'start_date' => 'required|date',
           'end_date' => 'required|date|after_or_equal:start_date',
           'color' => 'nullable|string|max:7', // Hex color code
       ]);

       $event = Event::create([
           'title' => $request->title,
           'description' => $request->description,
           'start_date' => $request->start_date,
           'end_date' => $request->end_date,
           'color' => $request->color ?? '#3b82f6', // Default color
           'user_id' => Auth::id(),
       ]);

       return response()->json($event, 201);
   }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'color' => 'nullable|string|max:7', // Hex color code
        ]);

        $event->update($request->only(['title', 'description', 'start_date', 'end_date', 'color']));

        return response()->json($event);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $event = Event::find($id);
    
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }
    
        // Soft delete the event
        $event->delete();
    
        return response()->json(['message' => 'Event soft deleted successfully']);
    }
}
