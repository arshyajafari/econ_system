<?php

namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Employee;
use App\Models\User;
use App\Security\PermissionSyncService;
use App\Security\RoleSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create an active administrator user and employee account for initial access';

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

        $firstName = trim((string) $this->ask('First name'));
        $lastName = trim((string) $this->ask('Last name'));
        $nationalCode = trim((string) $this->ask('National code'));
        $phoneNumber = trim((string) $this->ask('Phone number'));
        $hireDate = trim((string) $this->ask('Hire date (YYYY-MM-DD)', now()->toDateString()));

        if ($firstName === '' || mb_strlen($firstName) > 100) {
            $this->error('First name is required and must be at most 100 characters.');

            return self::FAILURE;
        }

        if ($lastName === '' || mb_strlen($lastName) > 100) {
            $this->error('Last name is required and must be at most 100 characters.');

            return self::FAILURE;
        }

        if ($nationalCode === '' || mb_strlen($nationalCode) > 25) {
            $this->error('National code is required and must be at most 25 characters.');

            return self::FAILURE;
        }

        if (Employee::query()->where('national_code', $nationalCode)->exists()) {
            $this->error("An employee with national code [{$nationalCode}] already exists.");

            return self::FAILURE;
        }

        if ($phoneNumber === '' || mb_strlen($phoneNumber) > 20) {
            $this->error('Phone number is required and must be at most 20 characters.');

            return self::FAILURE;
        }

        if (Employee::query()->where('phone_number', $phoneNumber)->exists()) {
            $this->error("An employee with phone number [{$phoneNumber}] already exists.");

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

        $hireDateTimestamp = strtotime($hireDate);
        if ($hireDateTimestamp === false || date('Y-m-d', $hireDateTimestamp) !== $hireDate) {
            $this->error('Hire date must use the YYYY-MM-DD format.');

            return self::FAILURE;
        }

        $permissionSyncService->sync();
        $roleSyncService->sync();

        DB::transaction(function () use (
            $firstName,
            $lastName,
            $nationalCode,
            $phoneNumber,
            $hireDate,
            $login,
            $password,
        ): void {
            $employee = Employee::query()->create([
                'code' => Employee::generateCode(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'national_code' => $nationalCode,
                'phone_number' => $phoneNumber,
                'gender' => Gender::MALE,
                'employment_type' => EmploymentType::FULL_TIME,
                'hire_date' => $hireDate,
                'status' => EmployeeStatus::ACTIVE,
            ]);

            $user = User::query()->create([
                'employee_id' => $employee->id,
                'login' => $login,
                'password' => $password,
                'status' => UserStatus::ACTIVE,
            ]);

            $user->assignRole(Role::ADMIN->value);
        });

        $this->info("Administrator [{$login}] created successfully with an active employee account.");

        return self::SUCCESS;
    }
}
