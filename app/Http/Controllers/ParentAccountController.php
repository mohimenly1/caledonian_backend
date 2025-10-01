<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParentInfo;

class ParentAccountController extends Controller
{
    //
    public function index(Request $request)
{
    $query = ParentInfo::query();

    // Eager load the necessary relationships
  // نقوم بتحميل الطلاب، ومع كل طالب نقوم بتحميل فصله وقسمه
$query->with(['user', 'students.class', 'students.section']);

    if ($request->has('search')) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('first_name', 'like', "%{$searchTerm}%")
              ->orWhere('last_name', 'like', "%{$searchTerm}%");
        });
    }

    return $query->latest()->paginate($request->get('per_page', 15));
}
}
