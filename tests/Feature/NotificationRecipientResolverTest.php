<?php

namespace Tests\Feature;

use App\Enums\EmployeeActivityType;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
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
        $this->assertSame(
            EmployeeActivityType::DELIVERY_OPERATOR,
            $recipients->first()->employee->activity_type,
        );
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

    public function test_notification_stores_the_exact_recipient_position_snapshot(): void
    {
        $user = $this->createUserWithEmployee(EmployeeActivityType::DELIVERY_OPERATOR);

        $notification = new SystemMessageNotification(
            title: 'Test',
            body: 'Body',
            priority: 'normal',
            senderId: 1,
            messageId: 'test-message-id',
            targetType: 'positions',
            targetValues: [EmployeeActivityType::DELIVERY_OPERATOR->value],
        );

        $payload = $notification->toArray($user->load('employee'));

        $this->assertSame($user->id, $payload['recipient_user_id']);
        $this->assertSame($user->employee_id, $payload['recipient_employee_id']);
        $this->assertSame(
            EmployeeActivityType::DELIVERY_OPERATOR->value,
            $payload['recipient_activity_type'],
        );
        $this->assertSame(
            [EmployeeActivityType::DELIVERY_OPERATOR->value],
            $payload['target_values'],
        );
    }



    public function test_targeted_notification_is_not_visible_to_another_user_even_if_a_row_exists_for_them(): void
    {
        $targetUser = $this->createUserWithEmployee(EmployeeActivityType::DELIVERY_OPERATOR);
        $otherUser = $this->createUserWithEmployee(EmployeeActivityType::ACCOUNTANT);

        $notification = new SystemMessageNotification(
            title: 'Targeted',
            body: 'Only the delivery operator should see this.',
            priority: 'normal',
            senderId: $targetUser->id,
            messageId: 'targeted-user-message',
            targetType: 'users',
            targetValues: [$targetUser->public_id],
        );

        $targetUser->notify($notification);
        $otherUser->notify($notification);

        $this->actingAs($targetUser)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($otherUser)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_position_notification_is_not_visible_to_a_different_position(): void
    {
        $deliveryUser = $this->createUserWithEmployee(EmployeeActivityType::DELIVERY_OPERATOR);
        $accountantUser = $this->createUserWithEmployee(EmployeeActivityType::ACCOUNTANT);

        $notification = new SystemMessageNotification(
            title: 'Position targeted',
            body: 'Only delivery operators should see this.',
            priority: 'normal',
            senderId: $deliveryUser->id,
            messageId: 'targeted-position-message',
            targetType: 'positions',
            targetValues: [EmployeeActivityType::DELIVERY_OPERATOR->value],
        );

        $deliveryUser->notify($notification);
        $accountantUser->notify($notification);

        $this->actingAs($deliveryUser)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($accountantUser)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
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
