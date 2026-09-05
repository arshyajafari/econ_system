<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Auth\LoginAction;
    use App\Actions\Auth\LogoutAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Auth\LoginRequest;
    use App\Http\Resources\UserResource;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;

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

        public function logout(Request $request, LogoutAction $action): JsonResponse {
            $action->execute($request);

            return response()->json([
                'success' => true,
                'message' => 'با موفقیت خارج شدید.',
            ]);
        }
    }
