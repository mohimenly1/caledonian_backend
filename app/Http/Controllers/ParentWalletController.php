<?php

namespace App\Http\Controllers;


use App\Models\ParentInfo;
use App\Models\ParentWallet;
use Illuminate\Http\Request;
use App\Notifications\WalletChargedNotification; // <-- تأكد من استدعاء هذا الإشعار
use App\Models\CaledonianNotification;
use Illuminate\Support\Facades\DB;


use Illuminate\Support\Facades\Auth;

class ParentWalletController extends Controller
{
    public function getParentsWithoutWallets()
    {
        // Get parents that do not have an associated wallet
        $parentsWithoutWallets = ParentInfo::doesntHave('wallet')->get();

        return response()->json($parentsWithoutWallets);
    }
    // Display a listing of the wallets
    public function index(Request $request)
    {
        // Create a query to fetch parent wallets with parent details
        $query = ParentWallet::with('parent');

        // Check if there is a search term in the request
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;

            $query->whereHas('parent', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", '%' . $searchTerm . '%'); // This line can stay if you prefer to use it for performance reasons.
            });
        }

        // Execute the query and return the results
        return response()->json($query->get());
    }

    public function getTransactions(ParentWallet $wallet)
{
    // قمنا بإزالة ->with('processedByUser:id,name')
    $transactions = $wallet->transactions()
                            ->latest() 
                            ->get();

    return response()->json($transactions);
}

    // Store a newly created wallet in storage
// في ParentWalletController.php

public function store(Request $request)
{
    $validatedData = $request->validate([
        'parent_id' => 'required|exists:parents,id|unique:parent_wallets,parent_id',
        'balance' => 'required|numeric|min:0',
    ]);

    $wallet = ParentWallet::create([
        'parent_id' => $validatedData['parent_id'],
        'user_id' => Auth::id(),
        'balance' => $validatedData['balance'],
    ]);

    // --- ✨ بداية منطق حفظ وإرسال الإشعار ✨ ---
    
    // 1. جلب بيانات ولي الأمر وحساب المستخدم المرتبط به
    $parent = ParentInfo::find($validatedData['parent_id']);
    
    // التحقق من وجود ولي الأمر وحساب مستخدم مرتبط به
    if ($parent && $parent->user) {
        $user = $parent->user;
        $amount = $validatedData['balance'];
        $parentName = $parent->first_name;

        // 2. بناء بيانات الإشعار
        $title = 'تم إنشاء وشحن المحفظة';
        $body = "السيد / {$parentName}، تم إنشاء محفظتك بنجاح وشحنها بقيمة {$amount} دينار.";

        // ✨ 3. حفظ الإشعار في قاعدة البيانات (الإضافة الجديدة) ✨
        CaledonianNotification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'data'    => ['type' => 'wallet_charge']
        ]);

        // 4. إرسال الإشعار اللحظي (FCM) كما كان
        if ($user->fcm_token) {
            $user->notify(new WalletChargedNotification($amount, $parentName));
        }
    }
    // --- نهاية منطق الإشعار ---

    return response()->json($wallet, 201);
}


    // Display the specified wallet
    public function show($id)
    {
        $wallet = ParentWallet::with('parent', 'user')->findOrFail($id);
        return response()->json($wallet);
    }

    // Update the specified wallet in storage
    public function update(Request $request, $id)
    {
        $wallet = ParentWallet::findOrFail($id);

        $request->validate([
            'parent_id' => 'sometimes|exists:parents,id',
            'balance' => 'sometimes|numeric|min:0',
        ]);

        // Optionally, you can also update the user_id if necessary
        $wallet->update($request->only('parent_id', 'balance'));

        return response()->json($wallet);
    }


    public function addFunds(Request $request, ParentWallet $wallet)
    {
        // 1. التحقق من صحة جميع البيانات المطلوبة، بما في ذلك الخزينة وطريقة الدفع
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'treasury_id' => 'required|exists:treasuries,id',
            'payment_method' => 'required|string|in:cash,card,transfer', // عدّل حسب طرق الدفع لديك
        ]);
    
        $amount = $validated['amount'];
        $parent = $wallet->parent;
    
        // 2. استخدام transaction لضمان تنفيذ العمليتين (تحديث الرصيد وتسجيل الحركة) معاً
        // إذا فشلت إحدى العمليات، يتم التراجع عن الأخرى تلقائياً
        DB::transaction(function () use ($wallet, $parent, $validated, $amount) {
            // 2a. تحديث رصيد المحفظة
            $wallet->increment('balance', $amount);
    
            // 2b. إنشاء سجل جديد في جدول transactions المركزي وربطه بالمحفظة
            $wallet->transactions()->create([
                'treasury_id' => $validated['treasury_id'],
                'payment_date' => now(), // استخدام التاريخ والوقت الحالي
                'amount' => $amount,
                'type' => 'income', // الإيداع في المحفظة يعتبر "دخل" للخزينة
                'payment_method' => $validated['payment_method'],
                'description' => "إيداع رصيد في محفظة ولي الأمر: {$parent->first_name} {$parent->last_name}",
                // 'related_id' و 'related_type' يتم ملؤهما تلقائياً بفضل العلاقة
            ]);
        });
        
        // --- 3. الجزء الخاص بالإشعارات (يبقى كما هو) ---
        $user = $parent->user; 
        if ($user) {
            // بناء العنوان والنص
            $title = 'تم شحن المحفظة';
            $body = "السيد / {$parent->first_name}، تم شحن محفظتك بقيمة {$amount} دينار.";
    
            // حفظ الإشعار في قاعدة البيانات
            CaledonianNotification::create([
                'user_id' => $user->id,
                'title'   => $title,
                'body'    => $body,
                'data'    => ['type' => 'wallet_charge']
            ]);
    
            // إرسال الإشعار اللحظي (FCM)
            if ($user->fcm_token) {
                $user->notify(new WalletChargedNotification($amount, $parent->first_name));
            }
        }
        // --- نهاية جزء الإشعار ---
    
        // 4. إرجاع استجابة ناجحة مع بيانات المحفظة المحدثة
        return response()->json([
            'message' => 'Funds added, transaction logged, and notification sent successfully.',
            'wallet' => $wallet->fresh(), // .fresh() لجلب أحدث نسخة من البيانات من قاعدة البيانات
        ]);
    }
    // Remove the specified wallet from storage
    public function destroy($id)
    {
        $wallet = ParentWallet::findOrFail($id);
        $wallet->delete();
        return response()->json(null, 204); // Return a 204 No Content response
    }
}
