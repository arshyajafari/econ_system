<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Auth\LoginAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Auth\LoginRequest;
    use App\Http\Resources\UserResource;
    use Illuminate\Http\JsonResponse;

    class AuthController extends Controller {
        public function login(LoginRequest $request, LoginAction $action): JsonResponse {
            $result = $action->execute($request->validated());

            return response()->json([
                'success' => true,
                'message' => __('auth.login_success'),
                'data' => [
                    'token' => $result['token'],
                    'user' => new UserResource($result['user']),
                ],
            ]);
        }
    }
