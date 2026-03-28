<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponder
{
    public static function success(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    public static function error(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['message' => $message], $extra), $status);
    }
}