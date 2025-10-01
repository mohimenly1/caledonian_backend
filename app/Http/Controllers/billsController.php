<?php

namespace App\Http\Controllers;

use App\Models\EmployeeWallet;
use App\Models\KitchenBill;
use App\Models\KitchenBillItem;
use App\Models\ParentWallet;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class billsController extends Controller
{

    public function updateBillItems(Request $request, $id)
    {


        $newTotalPrice = 0;
        foreach ($request->items as $editenItem) {
            $newTotalPrice = $newTotalPrice + ($editenItem['quantity'] * $editenItem['piece_price']);
        }


        // dd($newTotalPrice);

        $oldTotalPrice = 0;
        foreach ($request->items as $oldItem) {

            $currantItem =   KitchenBillItem::where('id', '=', $oldItem['id'])->get()[0];


            $oldTotalPrice = $oldTotalPrice +   ($currantItem['quantity'] * $currantItem['piece_price']);
        }

        $validated = $request->validate([
            'items' => 'required',
            'items.*.id' => 'required',
            'items.*.quantity' => 'required',
        ]);


        // التحقق من تاريخ الفاتورة
        $billDate = Carbon::parse($request->created_at)->toDateString(); // تاريخ الفاتورة
        $todayDate = Carbon::today()->toDateString(); // تاريخ اليوم

        if ($billDate !== $todayDate) {
            return response()->json(['error' => 'لا يمكنك تعديل الفاتورة لأنها ليست بتاريخ اليوم.'], 403);
        }

        foreach ($request->items as $item) {

            KitchenBillItem::where('id', '=', $item['id'])
                ->update([
                    'quantity' => $item['quantity'],
                    'meal_price' => ($item['quantity'] * $item['piece_price'])
                ]);
        }
        $currantBill = KitchenBill::where('id', '=', $request->bill_id)->get()[0];
        if ($currantBill->payment_method == "wallet") {
            if ($currantBill->buyer_type == "employee") {
                $currantbuyer = EmployeeWallet::where('employee_id', '=', $currantBill->buyer_id)->get()[0];
                $cuurntBalance = $currantbuyer->balance;
                $currantbuyer->update([
                    'balance' => $cuurntBalance + ($oldTotalPrice - $newTotalPrice)
                ]);
                //  dd($cuurntBalance);
            }

            if ($currantBill->buyer_type == "student") {
                $currantStudent = Student::where('id', '=', $currantBill->buyer_id)->get()[0];
                $currantbuyer = ParentWallet::where('parent_id', '=', $currantStudent['parent_id'])->get()[0];
                $cuurntBalance = $currantbuyer->balance;
                $currantbuyer->update([
                    'balance' => $cuurntBalance + ($oldTotalPrice - $newTotalPrice)
                ]);
                //  dd($cuurntBalance);
            }

            // dd($oldTotalPrice - $newTotalPrice);
        }
    }

    public function show_bill()
    {

        $query = KitchenBill::with(['items', 'items.meal']);
        if (request()->buyer_type == 'employee')
            $query->with(['employee']);
        if (request()->buyer_type == 'student')
            $query->with(['student']);
        $query->with(['MealCategory']);

        $data = $query->where('id', '=', request()->bill_id)
            ->limit(1)
            ->get()[0];


        return response()->json($data);
    }
    public function fetch_bills(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'billNumber' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $query = KitchenBill::where('buyer_type', $request->buyer_type);
        $query->whereBetween('created_at', [
            Carbon::parse($request->selectedDate)->startOfDay(),
            Carbon::parse($request->selectedEndDate)->endOfDay()
        ]);


        if ($request->billNumber != NULL)
            $query->where('bill_number', '=', $request->billNumber);

        if ($request->buyer_id != NULL && $request->buyer_type != 'visitor')
            $query->where('buyer_id', $request->buyer_id);

        if ($request->payment_method != NULL && $request->payment_method != 'all')
            $query->where('payment_method', $request->payment_method);

        if ($request->buyer_type == 'student')
            $query->with(['student']);

        if ($request->buyer_type == 'employee')
            $query->with(['employee']);

        $query->with(['items']);
        $query->with(['items.meal']);
        $query->with(['MealCategory']);



        $data = $query->paginate(10);

        return response()->json($data);
    }
}
