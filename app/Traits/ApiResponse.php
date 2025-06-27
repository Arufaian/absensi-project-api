<?php

namespace App\Traits;

trait ApiResponse
{
    protected function success($data = null, $message = 'success', $status = 200)
    {
        return response() -> json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error($message = 'error', $status = 400, $errors = null)
    {
        return response() -> json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}