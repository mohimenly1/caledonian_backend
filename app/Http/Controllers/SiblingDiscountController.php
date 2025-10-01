<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiblingDiscount;
use Illuminate\Http\Request;

class SiblingDiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'study_year_id' => 'required|exists:study_years,id'
        ]);

        $discounts = SiblingDiscount::where('study_year_id', $request->study_year_id)
            ->orderBy('number_of_siblings')
            ->get();

        return response()->json($discounts);
    }

    /**
     * Store or update sibling discount policies.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'study_year_id' => 'required|exists:study_years,id',
            'discounts' => 'required|array',
            'discounts.*.number_of_siblings' => 'required|integer|min:2',
            'discounts.*.discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($validated['discounts'] as $discountData) {
            SiblingDiscount::updateOrCreate(
                [
                    'study_year_id' => $validated['study_year_id'],
                    'number_of_siblings' => $discountData['number_of_siblings'],
                ],
                [
                    'discount_percentage' => $discountData['discount_percentage'],
                ]
            );
        }

        return response()->json(['message' => 'Sibling discount policy saved successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SiblingDiscount $siblingDiscount)
    {
        $siblingDiscount->delete();
        return response()->json(['message' => 'Discount rule deleted successfully.']);
    }
}
