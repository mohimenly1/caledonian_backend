<?php

namespace App\Http\Controllers;

use App\Models\ParentInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentSupervisorController extends Controller
{
    public function verify_parent_identity_page(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'required|numeric|exists:parents,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = ParentInfo::where('id', $request->parent_id)->get(
            [
                'id',
                'first_name',
                'last_name',
            ]
        );

        return response()->json(['message' => 'success', 'data' => $data], 201);
    }
    public function parent_chaildrens_data(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'required|numeric|exists:parents,id',
            'pin_code'  => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = ParentInfo::with(['students', 'students.class', 'students.section'])
            ->where('id', $request->parent_id)
            ->where('pin_code', $request->pin_code)
            ->get(
                //     [
                //     'id',
                //     'parents.first_name',
                //     'parents.last_name',

                //     // 'students.name'
                // ]
            );

        if (count($data) == 0) {

            return response()->json(['errors' => ['pin_code' => ['pin code is not correct']]], 422);
        }
        return response()->json(['message' => 'success', 'data' => $data], 201);
    }
}
