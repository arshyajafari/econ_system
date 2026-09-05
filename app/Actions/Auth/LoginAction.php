<?php

    namespace App\Actions\Auth;

    use App\Enums\EmployeeStatus;
    use App\Models\Device;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Validation\ValidationException;

    class LoginAction {
        public function execute(array $data): array {
            return DB::transaction(function () use ($data) {
                $user = User::query()->with('employee')->active()->where('login', $data['login'])->first();

                if (!$user || !Hash::check($data['password'], $user->password)) {
                    throw ValidationException::withMessages([
                        'login' => __('auth.failed'),
                    ]);
                }

                if (!$user->employee) {
                    throw ValidationException::withMessages([
                        'login' => __('auth.failed'),
                    ]);
                }

                if ($user->employee->status !== EmployeeStatus::ACTIVE) {
                    throw ValidationException::withMessages([
                        'login' => __('auth.failed'),
                    ]);
                }

                $device = Device::updateOrCreate([
                        'device_id' => $data['device_id'],
                    ], [
                        'user_id' => $user->id,
                        'platform' => $data['platform'],
                        'platform_version' => $data['platform_version'] ?? null,
                        'app_version' => $data['app_version'] ?? null,
                        'push_token' => $data['push_token'] ?? null,
                        'last_seen_at' => now(),
                        'last_ip' => request()->ip(),
                    ]);

                $token = $user->createToken($device->device_id, ['*'])->plainTextToken;

                $user->update([
                    'last_login_at' => now(),
                    'last_login_ip' => request()->ip(),
                ]);

                return [
                    'token' => $token,
                    'user' => $user->fresh(['employee']),
                    'device' => $device,
                ];
            });
        }
    }
