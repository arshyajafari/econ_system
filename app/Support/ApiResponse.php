<?php

    namespace App\Support;

    use Illuminate\Http\JsonResponse;

    class ApiResponse {
        public static function success(mixed $data = null, string $message = 'عملیات با موفقیت انجام شد.',
            int $status = 200, array $meta = []): JsonResponse {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
                'errors' => [],
                'meta' => $meta
            ], $status);
        }

        public static function error(string $message, array $errors = [], int $status = 422): JsonResponse {
            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $errors,
                'meta' => []
            ], $status);
        }
    }
