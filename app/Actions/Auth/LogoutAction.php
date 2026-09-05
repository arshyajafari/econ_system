<?php

    namespace App\Actions\Auth;

    use Illuminate\Http\Request;

    class LogoutAction {
        public function execute(Request $request): void {
            $token = $request->user()?->currentAccessToken();

            if ($token) {
                $token->delete();
            }
        }
    }
