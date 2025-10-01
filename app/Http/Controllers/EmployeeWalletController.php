<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeWallet;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class EmployeeWalletController extends Controller
{
    public function getEmployeesWithoutWallets()
    {
        // Get employees that do not have an associated wallet
        $employeesWithoutWallets = Employee::doesntHave('wallet')->get();

        return response()->json($employeesWithoutWallets);
    }
    // Display a listing of the wallets
    public function index(Request $request)
    {
        // Create a query to fetch employee wallets with employee details
        $query = EmployeeWallet::with('employee');

        // Check if there is a search term in the request
        if ($request->has('search') && $request->search != '') {
            $query->whereHas('employee', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Execute the query and return the results
        return response()->json($query->get());
    }

    // Store a newly created wallet in storage
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id|unique:employee_wallets,employee_id',
            'balance' => 'required|numeric|min:0',
        ]);

        // Get the authenticated user's ID
        $userId = Auth::user()->id; // or request()->user()->id

        $wallet = EmployeeWallet::create([
            'employee_id' => $request->employee_id,
            'user_id' => $userId, // Set the user_id from authenticated user
            'balance' => $request->balance,
        ]);

        return response()->json($wallet, 201); // Return created wallet with a 201 status
    }

    // Display the specified wallet
    public function show($id)
    {
        $wallet = EmployeeWallet::with('employee', 'user')->findOrFail($id);
        return response()->json($wallet);
    }

    // Update the specified wallet in storage
    public function update(Request $request, $id)
    {
        $wallet = EmployeeWallet::findOrFail($id);

        $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'balance' => 'sometimes|numeric|min:0',
        ]);

        // Optionally, you can also update the user_id if necessary
        $wallet->update($request->only('employee_id', 'balance'));

        return response()->json($wallet);
    }

    // Remove the specified wallet from storage
    public function destroy($id)
    {
        $wallet = EmployeeWallet::findOrFail($id);
        $wallet->delete();
        return response()->json(null, 204); // Return a 204 No Content response
    }
}
