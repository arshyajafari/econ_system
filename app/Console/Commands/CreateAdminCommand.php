<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Security\PermissionSyncService;
use App\Security\RoleSyncService;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create an active administrator user for initial access';

    public function handle(
        PermissionSyncService $permissionSyncService,
        RoleSyncService $roleSyncService,
    ): int {
        $login = trim((string) $this->ask('Login'));

        if ($login === '' || mb_strlen($login) > 50) {
            $this->error('Login is required and must be at most 50 characters.');

            return self::FAILURE;
        }

        if (User::query()->where('login', $login)->exists()) {
            $this->error("A user with login [{$login}] already exists.");

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        if (mb_strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        if (! hash_equals($password, $passwordConfirmation)) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $permissionSyncService->sync();
        $roleSyncService->sync();

        $user = User::query()->create([
            'login' => $login,
            'password' => $password,
            'status' => UserStatus::ACTIVE,
        ]);

        $user->assignRole(Role::ADMIN->value);

        $this->info("Administrator [{$login}] created successfully.");

        return self::SUCCESS;
    }
}
