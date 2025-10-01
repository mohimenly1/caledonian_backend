<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FinancialDocument;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Notifications\StoreFinancialDocumentNotification;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialDocumentController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 10); // Default items per page

        // Fetch paginated financial documents with related models
        $financialDocuments = FinancialDocument::with(['student', 'parent', 'subscriptionFees'])
            ->where(function ($query) use ($search) {
                $query->where('receipt_number', 'like', "%$search%")
                    ->orWhereHas('parent', function ($parentQuery) use ($search) {
                        $parentQuery->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%")
                            ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%$search%")
                            ->orWhereHas('students', function ($studentQuery) use ($search) {
                                $studentQuery->Where('name', 'like', "%$search%");
                                // ->orWhere('arabic_name', 'LIKE', "%{$search}%");
                            });
                    });
            })
            ->whereNotNull('total_amount')
            ->whereNotNull('final_amount')
            ->whereNotNull('value_received')
            ->paginate($perPage)
            ->through(function ($document) {
                $parent = $document->parent;

                // Prepare subscription fees data
                $subscriptionFees = $document->subscriptionFees->map(function ($fee) use ($document) {
                    // Fetch the correct student based on the pivot's student_id
                    $student = Student::find($fee->pivot->student_id);

                    return [
                        'subscription_fee_id' => $fee->id,
                        'category' => $fee->category,
                        'sub_category' => $fee->sub_category,
                        'student_name' => $student ? $student->name : 'N/A',
                        'amount' => $fee->pivot->amount ?? 'N/A', // Fee amount from pivot table
                    ];
                });

                // Return financial document with all related data
                return [
                    'id' => $document->id,
                    'receipt_number' => $document->receipt_number,
                    'parent_name' => $parent ? $parent->first_name . ' ' . $parent->last_name : 'N/A',
                    'total_amount' => $document->total_amount,
                    'discount' => $document->discount,
                    'final_amount' => $document->final_amount,
                    'value_received' => $document->value_received,
                    'payment_method' => $document->payment_method,
                    'bank_name' => $document->bank_name,
                    'branch_name' => $document->branch_name,
                    'account_number' => $document->account_number,
                    'subscription_fees' => $subscriptionFees, // Include the fees with students
                ];
            });

        return response()->json($financialDocuments);
    }




    public function updateFinancialDocument(Request $request, $id)
    {
        // Find the financial document
        $financialDocument = FinancialDocument::findOrFail($id);

        // Prepare validation rules
        $rules = [
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,instrument',
            'value_received' => 'required|numeric|min:0',
        ];

        // Only validate student_fees if they are being updated
        if ($request->has('student_fees')) {
            $rules['student_fees'] = 'required|array';
            $rules['student_fees.*.subscription_fee_id'] = 'required|exists:subscription_fees,id';
            $rules['student_fees.*.amount'] = 'required|numeric|min:0';
        }

        // Validate the request
        $validatedData = $request->validate($rules);

        // Get the original value received to adjust the treasury balance
        $originalValueReceived = $financialDocument->value_received;

        // Get the treasury and update its balance
        $treasury = Treasury::findOrFail($financialDocument->treasury_id);
        $treasury->balance -= $originalValueReceived;  // Remove old value
        $treasury->balance += $validatedData['value_received']; // Add new value
        $treasury->save();

        // Calculate total amount with student fees if provided
        $totalAmount = $financialDocument->total_amount; // Assuming this is the correct field
        $discount = $validatedData['discount'] ?? 0; // Default to 0 if not provided
        $finalAmount = $totalAmount - $discount; // Calculate final amount


        // Calculate remaining amount (should be 0 if value_received >= final_amount)
        $remainingAmount = $finalAmount - $validatedData['value_received'];
        if ($remainingAmount < 0) {
            $remainingAmount = 0; // Ensure remaining amount doesn't go below 0
        }


        // Update the financial document with new values
        $financialDocument->update([
            'discount' => $discount,
            'value_received' => $validatedData['value_received'],
            'final_amount' => $finalAmount, // Ensure final_amount is updated here
            'remaining_amount' => $remainingAmount, // Update remaining amount
        ]);

        // Update subscription fees if student fees are provided
        if (isset($validatedData['student_fees'])) {
            // Detach old subscription fees
            $financialDocument->subscriptionFees()->detach();

            // Attach new subscription fees
            foreach ($validatedData['student_fees'] as $fee) {
                $financialDocument->subscriptionFees()->attach($fee['subscription_fee_id'], [
                    'student_id' => $fee['student_id'],
                    'amount' => $fee['amount'],
                ]);
            }
        }

        return response()->json(['message' => 'Financial document updated successfully.']);
    }




    public function destroy($id)
    {
        // Find the financial document
        $financialDocument = FinancialDocument::findOrFail($id);

        // Get the value_received from the financial document
        $valueReceived = $financialDocument->value_received;

        // Retrieve the related treasury
        $treasury = Treasury::findOrFail($financialDocument->treasury_id);

        // Subtract the value from the treasury
        $treasury->balance -= $valueReceived;
        $treasury->save();

        // Soft delete the financial document, triggering the pivot record soft delete
        $financialDocument->delete();

        return response()->json([
            'message' => "The amount of $valueReceived has been subtracted from the treasury and the financial document has been deleted.",
            'value_received' => $valueReceived,
        ], 200);
    }



    public function confirmDeletion($id)
    {
        // Find the financial document
        $financialDocument = FinancialDocument::findOrFail($id);

        // Get the value_received from the financial document
        $valueReceived = $financialDocument->value_received;

        // Retrieve the related treasury
        $treasury = Treasury::findOrFail($financialDocument->treasury_id); // Ensure the treasury exists

        // Subtract value_received from treasury balance
        $treasury->balance -= $valueReceived;
        $treasury->save();

        // Detach the related subscription fees
        $financialDocument->subscriptionFees()->detach();

        // Delete the financial document itself
        $financialDocument->delete();

        return response()->json([
            'message' => 'Financial document deleted successfully, and the treasury has been updated.',
        ]);
    }




    public function generatePdf(Request $request)
    {
        $documentId = $request->input('id');
        $document = FinancialDocument::findOrFail($documentId);

        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('pdf.financial_document', compact('document'))->render());

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();
        $pdfPath = storage_path('app/public/financial_document_' . $document->id . '.pdf');
        file_put_contents($pdfPath, $pdfOutput);

        return response()->json(['pdfUrl' => url('storage/financial_document_' . $document->id . '.pdf')]);
    }

    public function storeFinancialDocument(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'student_fees' => 'required|array',
            'student_fees.*' => 'array',
            'student_fees.*.*.subscription_fee_id' => 'required|exists:subscription_fees,id',
            'student_fees.*.*.amount' => 'required|numeric|min:0',
            'treasury_id' => 'required|exists:treasuries,id',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,instrument',
            'value_received' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string',
            'branch_name' => 'nullable|string',
            'account_number' => 'nullable|string',
        ]);

        // Initialize totals and group students by parent
        $parentGroups = [];
        foreach ($validatedData['student_ids'] as $studentId) {
            $student = Student::findOrFail($studentId);
            $parentId = $student->parent_id;

            if (!$parentId) {
                return response()->json(['message' => 'Parent record not found for student ID: ' . $studentId], 404);
            }

            $parentGroups[$parentId][] = $studentId;
        }

        // Process each parent group
        foreach ($parentGroups as $parentId => $studentIds) {
            $overallTotalAmount = 0;

            // Calculate the overall total amount for the parent group
            foreach ($studentIds as $studentId) {
                $studentFees = $validatedData['student_fees'][$studentId];
                $totalAmount = array_reduce($studentFees, function ($carry, $fee) {
                    return $carry + $fee['amount'];
                }, 0);

                $overallTotalAmount += $totalAmount;
            }

            // Apply discount if provided
            $discount = $validatedData['discount'] ?? 0;
            $finalAmount = $overallTotalAmount - $discount;

            // Get treasury and update its balance with the value received
            $treasury = Treasury::findOrFail($validatedData['treasury_id']);
            $treasury->balance += $validatedData['value_received'];
            $treasury->save();


            $remainingAmount = $finalAmount - $validatedData['value_received'];

            // Get parent information
            $parent = ParentInfo::findOrFail($parentId);

            // Create a unique receipt number
            $receiptNumber = 'REC-' . strtoupper(uniqid());

            // Create the financial document for the parent group
            $financialDocument = FinancialDocument::create([
                'parent_id' => $parent->id,
                'treasury_id' => $validatedData['treasury_id'],
                'total_amount' => $overallTotalAmount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'payment_method' => $validatedData['payment_method'],
                'receipt_number' => $receiptNumber,
                'value_received' => $validatedData['value_received'],
                'remaining_amount' => $remainingAmount,  // Save remaining amount
            ]);

            ActivityLog::create([
                'user_id' => auth()->user()->id,
                'action' => 'Store Financial Document',
                'description' => 'Store Financial Document',
                'new_data' =>  json_encode(value: $financialDocument),
                'created_at' => now(),
            ]);

            $adminUsers = User::where('user_type', 'admin')->get();
            foreach ($adminUsers as $admin) {
                Log::info('Sending notification to admin user:', ['admin_id' => $admin->id, 'financial_document_id' => $financialDocument->id]);

                try {
                    $admin->notify(new StoreFinancialDocumentNotification($financialDocument));
                    Log::info('Notification sent successfully to admin user:', ['admin_id' => $admin->id]);
                } catch (\Exception $e) {
                    Log::error('Failed to send notification:', [
                        'admin_id' => $admin->id,
                        'financial_document_id' => $financialDocument->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            Log::info('Financial Document Created:', ['id' => $financialDocument->id]);

            if (!$financialDocument) {
                return response()->json(['message' => 'Failed to create financial document'], 500);
            }

            // Use $financialDocument->id to ensure you have the right ID
            $financialDocumentId = $financialDocument->id;

            // Attach the selected subscription fees to the financial document for each student
            foreach ($studentIds as $studentId) {
                $studentFees = $validatedData['student_fees'][$studentId];
                foreach ($studentFees as $fee) {
                    Log::info('Inserting into pivot:', [
                        'financial_document_id' => $financialDocumentId,
                        'subscription_fee_id' => $fee['subscription_fee_id'],
                        'student_id' => $studentId,
                        'amount' => $fee['amount'],
                    ]);

                    // Check if the financial document ID exists in the database
                    $documentExists = FinancialDocument::where('id', $financialDocumentId)->exists();
                    if (!$documentExists) {
                        Log::error('Financial Document does not exist.', ['id' => $financialDocumentId]);
                        return response()->json(['message' => 'Financial document does not exist.'], 404);
                    }

                    // Save in the pivot table between financial_documents and subscription_fees
                    $financialDocument->subscriptionFees()->attach($fee['subscription_fee_id'], [
                        'student_id' => $studentId,
                        'amount' => $fee['amount'],
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Financial document issued successfully.', 'receipt_number' => $receiptNumber]);
    }
}
