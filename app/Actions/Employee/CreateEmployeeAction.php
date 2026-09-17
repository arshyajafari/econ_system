<?php

namespace App\Actions\Employee;

use App\Enums\UserStatus;
use App\Models\Employee;
use App\Models\EmployeeActivity;
use App\Models\EmployeeAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEmployeeAction {
    public function execute(array $data): Employee {
        return DB::transaction(function () use ($data) {
            $addressData = $data['address'] ?? [];
            $activities = $data['activities'] ?? [];
            $login = $data['login'] ?? null;
            $password = $data['password'] ?? null;
            $roles = $data['roles'] ?? [];
            unset($data['address'], $data['activities'], $data['login'], $data['password'], $data['password_confirmation'], $data['roles']);

            $data['code'] = Employee::generateCode();
            $data['activity_type'] = $activities[0] ?? $data['activity_type'] ?? 'other';

            if (!$login || !$password || empty($roles)) {
                throw ValidationException::withMessages([
                    'login' => 'نام کاربری، رمز عبور و حداقل یک نقش برای ساخت حساب ورود الزامی است.',
                ]);
            }

            $employee = new Employee();
            $employee->fill($data);
            $employee->save();

            $this->syncActivities($employee, $activities ?: [$data['activity_type']]);

            $user = new User();
            $user->employee_id = $employee->id;
            $user->login = $login;
            $user->password = $password;
            $user->status = UserStatus::ACTIVE;
            $user->save();
            $user->syncRoles($roles);

            if (!empty($addressData)) {
                $address = new EmployeeAddress();
                $address->fill($addressData);
                $address->is_default = true;
                $employee->addresses()->save($address);
            }

            return $employee->fresh(Employee::DEFAULT_RELATIONS);
        });
    }

    private function syncActivities(Employee $employee, array $activities): void {
        EmployeeActivity::query()->where('employee_id', $employee->id)->delete();
        foreach (array_values(array_unique($activities)) as $activity) {
            EmployeeActivity::create([
                'employee_id' => $employee->id,
                'activity_type' => $activity,
            ]);
        }
    }
}
