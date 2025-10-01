<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KitchenBill;
use App\Models\ParentInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentWalletDashboardController extends Controller
{
    /**
     * Get all wallet and canteen related data for the authenticated parent.
     * GET /api/parent/wallet-dashboard
     */
    public function show()
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        if (!$parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        // 1. Load wallet and students with their purchase limits
        $parent->load(['wallet', 'students.purchaseCeiling']);

        // 2. Get all student IDs to fetch their combined purchase history
        $studentIds = $parent->students->pluck('id');

        // 3. Get all kitchen bills for all children, sorted by the most recent
        $purchaseHistory = KitchenBill::whereIn('buyer_id', $studentIds)
            ->where('buyer_type', 'student')
            ->with(['items.meal:id,name,price', 'student:id,name']) // Also get the student's name for each bill
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'wallet_balance' => $parent->wallet->balance ?? 0.0,
            'children' => $parent->students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'purchase_limit' => $student->purchaseCeiling->purchase_ceiling ?? null,
                ];
            }),
            'purchase_history' => $purchaseHistory,
        ]);
    }
}
