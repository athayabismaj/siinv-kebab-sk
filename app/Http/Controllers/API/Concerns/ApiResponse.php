<?php

namespace App\Http\Controllers\API\Concerns;

trait ApiResponse
{
    protected function successResponse(string $message, mixed $data = null, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse(string $message, mixed $data = null, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function unauthorizedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }
}
