<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeWallet;
use App\Models\KitchenBill;
use App\Models\KitchenBillItem;
use App\Models\Meal;
use App\Models\ParentInfo;
use App\Models\ParentWallet;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Treasury;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;


class kitchenController extends Controller
{

    public function total_price_for_today(Request $request)
    {
        // dd($request->all());
        if ($request->buyerType == "employee" || $request->buyerType == "visitor")
            return response()->json(['total_price_for_today' => null]);
        else {
            $customer = Student::where('id', '=', $request->selectCustomer)->get();

            return response()->json(['daily_allowed_purchase_value' => $customer[0]->daily_allowed_purchase_value]);
        }
    }
    public function students_customers()
    {
        // $data =   Student::with(['student_restricted_meal', 'mealFees', 'kitchen_bills_for_today', 'parent'])
        //     ->get(
        //         [
        //             'id',
        //             'name',
        //             'daily_allowed_purchase_value',


        //         ]
        //     );

        $data = Student::with(['student_restricted_meal', 'mealFees', 'kitchen_bills_for_today', 'parent.wallet'])
            ->get(['id', 'name', 'daily_allowed_purchase_value', 'parent_id']);

        return response()->json($data);
    }

    public function employees_customers()
    {
        $data =   Employee::with('wallet')->get(['id', 'name', 'pin']);
        return response()->json($data);
    }
    public function buying_from_kitchen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'buyer_id' => 'nullable|numeric',
            'buyer_type' => 'required|string|in:student,employee,visitor',
            'items' => 'required|array|min:1',
            'items.*.meal_id' => 'required|exists:meals,id',
            'items.*.quantity' => 'required|integer|min:1',
            'PaymentMethod' => 'required|string|in:cash,wallet,subscription',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $totalBillPrice = collect($request->items)->sum(function ($item) {
            $meal = Meal::find($item['meal_id']);
            return $meal ? $meal->price * $item['quantity'] : 0;
        });

        // --- Pre-purchase Validations for Students ---
        if ($request->buyer_type == 'student') {
            $student = Student::with(['purchaseCeiling', 'restrictedMeals', 'parent.wallet'])->findOrFail($request->buyer_id);

            // 1. Check for restricted meals
            $requestedMealIds = collect($request->items)->pluck('meal_id');
            $restrictedMealIds = $student->restrictedMeals->pluck('id');
            $intersection = $requestedMealIds->intersect($restrictedMealIds);

            if ($intersection->isNotEmpty()) {
                $restrictedMealName = Meal::find($intersection->first())->name;
                return response()->json(['message' => "Sale failed. The meal '{$restrictedMealName}' is restricted for this student."], 422);
            }

            // 2. Check daily purchase limit
            $purchaseLimit = $student->purchaseCeiling->purchase_ceiling ?? null;
            if ($request->PaymentMethod != 'subscription' && $purchaseLimit !== null) {
                $spentToday = KitchenBill::where('buyer_id', $student->id)
                    ->where('buyer_type', 'student')
                    ->whereDate('created_at', Carbon::today())
                    ->withSum('items', 'meal_price')
                    ->get()->sum('items_sum_meal_price');

                if (($spentToday + $totalBillPrice) > $purchaseLimit) {
                    return response()->json(['message' => 'Sale failed. Student has exceeded the daily purchase limit.'], 422);
                }
            }
            
            // 3. Check wallet balance
            if ($request->PaymentMethod == 'wallet') {
                $wallet = $student->parent->wallet;
                if (!$wallet || $wallet->balance < $totalBillPrice) {
                    return response()->json(['message' => 'Insufficient funds in the parent\'s wallet.'], 422);
                }
            }
        }
        
        // --- Database Transaction ---
        try {
            DB::beginTransaction();

            $kitchenBill = KitchenBill::create([
                'user_id' => Auth::id(),
                'buyer_type' => $request->buyer_type,
                'buyer_id' => $request->buyer_id,
                'payment_method' => $request->PaymentMethod,
                'bill_number' => $this->generateBillNumber(),
            ]);

            foreach ($request->items as $item) {
                $meal = Meal::find($item['meal_id']);
                $kitchenBill->items()->create([
                    'meal_id' => $item['meal_id'],
                    'quantity' => $item['quantity'],
                    'piece_price' => $meal->price,
                    'meal_price' => $meal->price * $item['quantity'],
                ]);
            }

            // --- التعديل الرئيسي هنا: توحيد منطق تسجيل الإيراد ---
            if ($request->PaymentMethod == 'cash' || $request->PaymentMethod == 'wallet') {
                $canteenTreasury = Treasury::where('name', 'صندوق المقصف')->firstOrFail();

                if ($request->PaymentMethod == 'cash') {
                    // For cash, simply add to the canteen treasury balance
                    $canteenTreasury->balance += $totalBillPrice;
                } elseif ($request->PaymentMethod == 'wallet') {
                    // For wallet, deduct from the wallet...
                    if ($request->buyer_type == 'student') {
                        $wallet = $student->parent->wallet;
                        $wallet->balance -= $totalBillPrice;
                        $wallet->save();
                    } elseif ($request->buyer_type == 'employee') {
                        $employee = Employee::findOrFail($request->buyer_id);
                        $wallet = $employee->wallet;
                        $wallet->balance -= $totalBillPrice;
                        $wallet->save();
                    }
                    // ...and also add the revenue to the canteen treasury balance
                    $canteenTreasury->balance += $totalBillPrice;
                }
                
                $canteenTreasury->save();
                
                // Always record the transaction for canteen revenue
                Transaction::create([
                    'treasury_id' => $canteenTreasury->id,
                    'payment_date' => Carbon::now(),
                    'amount' => $totalBillPrice,
                    'type' => 'income',
                    'payment_method' => $request->PaymentMethod,
                    'description' => "Canteen sale - Bill #" . $kitchenBill->bill_number,
                    'related_id' => $kitchenBill->id,
                    'related_type' => KitchenBill::class,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Bill saved successfully!', 'bill' => $kitchenBill], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateBillNumber()
    {
        $today = Carbon::today();
        $lastBillNumber = KitchenBill::whereDate('created_at', $today)->max('bill_number');
        return $lastBillNumber ? $lastBillNumber + 1 : 1;
    }

    public function getStudentPurchases(Student $student)
    {
        $purchases = $student->kitchen_bills()
                             ->with(['items.meal:id,name,price']) // Load items and meal details
                             ->orderBy('created_at', 'desc')
                             ->get();

        return response()->json($purchases);
    }
}
