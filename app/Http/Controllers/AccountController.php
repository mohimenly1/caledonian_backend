<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource in a hierarchical structure.
     */
    public function index(Request $request)
    {
        $query = Account::query();

        // Allow filtering by type, e.g., /api/accounts?type=income
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Fetch all relevant accounts
        $accounts = $query->get();

        // For a full chart of accounts, build a tree structure
        if (!$request->filled('type')) {
            $accounts = $this->buildTree($accounts);
        }
        
        return response()->json($accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:accounts,name',
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'parent_id' => 'nullable|exists:accounts,id',
        ]);

        $account = Account::create($validated);
        return response()->json($account, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        return response()->json($account->load('children'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('accounts')->ignore($account->id)],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'parent_id' => 'nullable|exists:accounts,id',
        ]);

        $account->update($validated);
        return response()->json($account);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        // Prevent deletion if the account has sub-accounts
        if ($account->children()->exists()) {
            return response()->json(['message' => 'Cannot delete an account that has sub-accounts.'], 422);
        }

        // Add logic here to check for transactions related to this account before deletion
        // For now, we allow deletion.

        $account->delete();
        return response()->json(['message' => 'Account deleted successfully.']);
    }

    /**
     * Helper function to build a tree structure from a flat collection.
     */
    protected function buildTree($elements, $parentId = null)
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element->parent_id == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                if ($children) {
                    $element->children = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
}
