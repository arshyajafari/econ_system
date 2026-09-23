<?php

namespace Tests\Feature;

use App\Enums\EmployeeActivityType;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\Employee;
use App\Models\User;
use App\Services\NotificationRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationRecipientResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_targeting_only_returns_users_with_the_exact_active_employee_position(): void
    {
        $deliveryUser = $this->createUserWithEmployee(EmployeeActivityType::DELIVERY_OPERATOR);
        $accountantUser = $this->createUserWithEmployee(EmployeeActivityType::ACCOUNTANT);
        $scientificUser = $this->createUserWithEmployee(EmployeeActivityType::SCIENTIFIC_VISITOR);

        $recipients = app(NotificationRecipientResolver::class)->resolve(
            targetType: 'positions',
            positionTypes: [EmployeeActivityType::DELIVERY_OPERATOR->value],
        );

        $this->assertSame([$deliveryUser->id], $recipients->modelKeys());
        $this->assertNotContains($accountantUser->id, $recipients->modelKeys());
        $this->assertNotContains($scientificUser->id, $recipients->modelKeys());
    }

    public function test_inactive_employee_is_not_a_position_recipient_even_when_the_user_is_active(): void
    {
        $user = $this->createUserWithEmployee(EmployeeActivityType::DELIVERY_OPERATOR);
        $user->employee->update(['status' => EmployeeStatus::INACTIVE->value]);

        $recipients = app(NotificationRecipientResolver::class)->resolve(
            targetType: 'positions',
            positionTypes: [EmployeeActivityType::DELIVERY_OPERATOR->value],
        );

        $this->assertNotContains($user->id, $recipients->modelKeys());
    }

    private function createUserWithEmployee(EmployeeActivityType $activityType): User
    {
        $employee = Employee::create([
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'gender' => Gender::MALE->value,
            'employment_type' => EmploymentType::FULL_TIME->value,
            'hire_date' => now()->toDateString(),
            'status' => EmployeeStatus::ACTIVE->value,
            'activity_type' => $activityType->value,
        ]);

        return User::create([
            'employee_id' => $employee->id,
            'login' => 'test_' . Str::lower(Str::random(10)),
            'password' => 'password',
            'status' => UserStatus::ACTIVE->value,
        ]);
    }
}
