<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // Get search query and current page
        $search = $request->get('search', '');
        $perPage = $request->get('perPage', 10); // Default items per page

        // Fetch activity logs with search and pagination
        $activityLogs = ActivityLog::with('user')
            ->where('description', 'LIKE', '%' . $search . '%')
            ->orWhereHas('user', function ($query) use ($search) {
                $query->where('username', 'LIKE', '%' . $search . '%');
            })
            ->paginate($perPage);

        return response()->json($activityLogs);
    }
}
