<?php

namespace App\Helpers;

class JsonResponseHelper {
    public static function response($status = 200, $success = true, $message = 'Success', $errors = [], $data = [])
    {
        return response()->json([
            "success" => $success,
            "message" => $message,
            "errors" => $errors,
            "data" => $data,
        ], $status);
    }

    public static function ok($message = 'Success', $errors = [], $data = [])
    {
        return self::response(200, true, $message, $errors, $data);
    }

    public static function badRequest($message = 'Bad request', $errors = [], $data = [])
    {
        return self::response(400, false, $message, $errors, $data);
    }

    public static function forbidden($message = 'Forbidden', $errors = [], $data = [])
    {
        return self::response(403, false, $message, $errors, $data);
    }

    public static function notFound($message = 'Not found', $errors = [], $data = [])
    {
        return self::response(404, false, $message, $errors, $data);
    }

    public static function internal($message = 'Internal', $errors = [], $data = [])
    {
        return self::response(500, false, $message, $errors, $data);
    }
}
