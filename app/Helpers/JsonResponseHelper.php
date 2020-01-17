<?php

namespace App\Helpers;

class JsonResponseHelper {
    public static function response($status = 200, $success = true, $message = 'Success', $errors = [])
    {
        return response()->json([
            "success" => $success,
            "message" => $message,
            "errors" => $errors
        ], $status);
    }
}