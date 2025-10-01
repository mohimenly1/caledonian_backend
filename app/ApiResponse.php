<?php

namespace App;

trait ApiResponse
{
    protected function success($data, $message = "Success", $status = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $status);
    }

    protected function successMessage($message = "Success", $status = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message
        ], $status);
    }

    protected function error($message = "Something went wrong", $status = 500)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $status);
    }

}