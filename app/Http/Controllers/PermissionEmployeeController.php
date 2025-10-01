<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PermissionEmployee;
use Illuminate\Http\Request;

class PermissionEmployeeController extends Controller
{
    // Get all permissions
    public function index()
    {
        $permissions = PermissionEmployee::with(['employee', 'approvedByUser'])
            ->paginate(10); // 10 items per page
        return response()->json($permissions);
    }
    
    public function getEmployee(Request $request)
    {
        $search = $request->input('search');

        $employees = Employee::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('national_number', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(10) // To prevent returning too many results
            ->get(['id', 'name']); // Return only required fields

        return response()->json($employees);
    }
    // Store a new permission
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'permission_type' => 'required|string',
            'request_date' => 'required|date',
            'approval_status' => 'nullable|in:approved,rejected,under_review',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'approved_by' => 'nullable|exists:users,id',
            'file_attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        if ($request->hasFile('file_attachment')) {
            $filePath = $request->file('file_attachment')->store('permissions_files', 'public');
            $validated['file_attachment'] = $filePath;
        }

        $permission = PermissionEmployee::create($validated);
        return response()->json($permission, 201);
    }

    // Show a specific permission
    public function show($id)
    {
        $permission = PermissionEmployee::with(['employee', 'approvedByUser'])->findOrFail($id);
        return response()->json($permission);
    }

    // Update a specific permission
    public function update(Request $request, $id)
    {
        $permission = PermissionEmployee::findOrFail($id);

        $validated = $request->validate([
            'permission_type' => 'sometimes|string',
            'request_date' => 'sometimes|date',
            'approval_status' => 'sometimes|in:approved,rejected,under_review',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'approved_by' => 'nullable|exists:users,id',
            'file_attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        if ($request->hasFile('file_attachment')) {
            $filePath = $request->file('file_attachment')->store('permissions_files', 'public');
            $validated['file_attachment'] = $filePath;
        }

        $permission->update($validated);
        return response()->json($permission);
    }

    // Soft delete a permission
    public function destroy($id)
    {
        $permission = PermissionEmployee::findOrFail($id);
        $permission->delete();
        return response()->json(['message' => 'Permission deleted successfully']);
    }

    // Restore a soft-deleted permission
    public function restore($id)
    {
        $permission = PermissionEmployee::withTrashed()->findOrFail($id);
        $permission->restore();
        return response()->json(['message' => 'Permission restored successfully']);
    }

    // List all soft-deleted permissions
    public function trashed()
    {
        $permissions = PermissionEmployee::onlyTrashed()->get();
        return response()->json($permissions);
    }
}
