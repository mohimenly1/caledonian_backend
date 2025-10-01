<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
    
        $query = Subscriber::query();
    
        if ($search) {
            $query->where('student_name', 'LIKE', "%{$search}%")
                  ->orWhere('school', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('parent_phone', 'LIKE', "%{$search}%")
                  ->orWhere('parent_email', 'LIKE', "%{$search}%");
        }
    
        $subscribers = $query->paginate($perPage);
    
        return response()->json($subscribers);
    }
    

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'student_name' => 'required|string|max:255',
            'age' => 'required|integer|min:1',
            'school' => 'nullable',
            'address' => 'required|string|max:255',
            'zip' => 'nullable',
            'parent_phone' => 'required|string|max:20',
            'parent_email' => 'nullable|string|email|max:255',
            'first_guardian_name' => 'nullable|string|max:255',
            'first_guardian_phone' => 'nullable|string|max:20',
            'second_guardian_name' => 'nullable|string|max:255',
            'second_guardian_phone' => 'nullable|string|max:20',
            'place_of_work' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'pickup_person_1_name' => 'nullable|string|max:255',
            'pickup_person_1_phone' => 'nullable|string|max:20',
            'pickup_person_2_name' => 'nullable|string|max:255',
            'pickup_person_2_phone' => 'nullable|string|max:20',
            'subscription_fee' => 'nullable|numeric',
            'paid' => 'nullable|numeric',
            'remaining' => 'nullable|numeric',
        ]);

        $subscriber = Subscriber::create($validatedData);

        return response()->json(['message' => 'Subscriber added successfully', 'subscriber' => $subscriber], 201);
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $validatedData = $request->validate([
            'student_name' => 'required|string|max:255',
            'age' => 'required|integer|min:1',
            'school' => 'nullable',
            'address' => 'required|string|max:255',
            'zip' => 'nullable',
            'parent_phone' => 'required|string|max:20',
            'parent_email' => 'nullable|string|email|max:255',
            'first_guardian_name' => 'nullable|string|max:255',
            'first_guardian_phone' => 'nullable|string|max:20',
            'second_guardian_name' => 'nullable|string|max:255',
            'second_guardian_phone' => 'nullable|string|max:20',
            'place_of_work' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'pickup_person_1_name' => 'nullable|string|max:255',
            'pickup_person_1_phone' => 'nullable|string|max:20',
            'pickup_person_2_name' => 'nullable|string|max:255',
            'pickup_person_2_phone' => 'nullable|string|max:20',
            'subscription_fee' => 'nullable|numeric',
            'paid' => 'nullable|numeric',
            'remaining' => 'nullable|numeric',
        ]);

        $subscriber->update($validatedData);

        return response()->json(['message' => 'Subscriber updated successfully', 'subscriber' => $subscriber]);
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return response()->json(['message' => 'Subscriber deleted successfully']);
    }
}
