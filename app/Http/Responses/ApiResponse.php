<?php
namespace App\Http\Responses;

class ApiResponse{
    public static function success($message = 'Success',$statusCode = 200,$data = [])
    {
        return response()->json([
            'message' => $message,
            'statusCode' => $statusCode,
            'error' => false,
            'data' => $data
        ],$statusCode);
    }

    public static function error($message = 'error',$statusCode,$errors=[])
    {
        return response()->json([
            'message' => $message,
            'statusCode' => $statusCode,
            'error' => true,
            'errors' => $errors
        ],$statusCode);
    }
}