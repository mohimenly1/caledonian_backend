<?php

namespace App\Http\Controllers;

use App\Models\StudentPurchaseCeiling;
use Illuminate\Http\Request;

class StudentPurchaseCeilingController extends Controller
{
      // Set purchase ceiling
      public function setCeiling(Request $request)
      {
          $request->validate([
              'student_id' => 'required|exists:students,id',
              'purchase_ceiling' => 'required|numeric|min:0',
          ]);
  
          $ceiling = StudentPurchaseCeiling::updateOrCreate(
              ['student_id' => $request->student_id],
              ['purchase_ceiling' => $request->purchase_ceiling]
          );
  
          return response()->json([
              'message' => 'Purchase ceiling set successfully!',
              'ceiling' => $ceiling
          ]);
      }
  
      // Get student ceiling
      public function getCeiling($student_id)
      {
          $ceiling = StudentPurchaseCeiling::where('student_id', $student_id)->first();
  
          if (!$ceiling) {
              return response()->json(['message' => 'Ceiling not found for this student!'], 404);
          }
  
          return response()->json($ceiling);
      }
}
