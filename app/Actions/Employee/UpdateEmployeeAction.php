<?php

namespace App\Actions\Employee;

use App\Enums\UserStatus;
use App\Models\Employee;
use App\Models\EmployeeActivity;
use App\Models\EmployeeAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateEmployeeAction {
    public function execute(Employee $employee, array $data): Employee {
        return DB::transaction(function () use ($employee, $data) {
            $addressData = $data['address'] ?? [];
            $activities = $data['activities'] ?? null;
            $login = $data['login'] ?? null;
            $password = $data['password'] ?? null;
            $roles = $data['roles'] ?? null;
            unset($data['address'], $data['activities'], $data['login'], $data['password'], $data['password_confirmation'], $data['roles']);

            if ($activities !== null && count($activities) > 0) {
                $data['activity_type'] = $activities[0];
            }

            $employee->fill($data);
            $employee->save();

            if ($activities !== null) {
                EmployeeActivity::query()->where('employee_id', $employee->id)->delete();
                foreach (array_values(array_unique($activities)) as $activity) {
                    EmployeeActivity::create([
                        'employee_id' => $employee->id,
                        'activity_type' => $activity,
                    ]);
                }
            }

            $user = $employee->user;
            if (!$user && $login && $password && is_array($roles) && count($roles) > 0) {
                $user = new User();
                $user->employee_id = $employee->id;
                $user->login = $login;
                $user->password = $password;
                $user->status = $employee->status->value === 'active' ? UserStatus::ACTIVE : UserStatus::INACTIVE;
                $user->save();
                $user->syncRoles($roles);
            } elseif ($user) {
                if ($login !== null) {
                    $user->login = $login;
                }
                if ($password !== null && $password !== '') {
                    $user->password = $password;
                }
                if ($employee->status->value !== 'active') {
                    $user->status = UserStatus::INACTIVE;
                } elseif ($user->status === UserStatus::INACTIVE) {
                    $user->status = UserStatus::ACTIVE;
                }
                $user->save();
                if ($roles !== null) {
                    $user->syncRoles($roles);
                }
            } elseif ($login || $password || ($roles !== null && count($roles) > 0)) {
                throw ValidationException::withMessages([
                    'login' => 'برای ساخت حساب ورود، نام کاربری، رمز عبور و حداقل یک نقش را کامل کنید.',
                ]);
            }

            if (!empty($addressData)) {
                $address = $employee->defaultAddress;
                if (!$address) {
                    $address = new EmployeeAddress();
                    $address->fill($addressData);
                    $address->is_default = true;
                    $employee->addresses()->save($address);
                } else {
                    $address->fill($addressData);
                    $address->save();
                }
            }

            return $employee->fresh(Employee::DEFAULT_RELATIONS);
        });
    }
}
