<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Policies\PaymentPolicy;
use PHPUnit\Framework\TestCase;

class PaymentPolicyUpdatePermissionTest extends TestCase
{
    public function test_settlement_operator_cannot_update_pending_payment_without_update_permission(): void
    {
        $user = $this->userWith(['settlement_operator']);

        $this->assertFalse((new PaymentPolicy())->update($user, $this->payment(PaymentStatus::PENDING)));
    }

    public function test_settlement_operator_with_update_permission_can_update_pending_payment(): void
    {
        $user = $this->userWith(['settlement_operator'], ['payments.update']);

        $this->assertTrue((new PaymentPolicy())->update($user, $this->payment(PaymentStatus::PENDING)));
    }

    public function test_accountant_without_update_permission_cannot_update_pending_payment(): void
    {
        $user = $this->userWith(['accountant']);

        $this->assertFalse((new PaymentPolicy())->update($user, $this->payment(PaymentStatus::PENDING)));
    }

    public function test_admin_can_update_pending_payment_without_separate_permission_check(): void
    {
        $user = $this->userWith(['admin']);

        $this->assertTrue((new PaymentPolicy())->update($user, $this->payment(PaymentStatus::PENDING)));
    }

    public function test_confirmed_payment_cannot_be_updated_even_with_permission(): void
    {
        $user = $this->userWith(['admin', 'accountant', 'settlement_operator'], ['payments.update']);

        $this->assertFalse((new PaymentPolicy())->update($user, $this->payment(PaymentStatus::CONFIRMED)));
    }

    private function payment(PaymentStatus $status): Payment
    {
        $payment = new Payment();
        $payment->setAttribute('status', $status);

        return $payment;
    }

    /**
     * @param list<string> $roles
     * @param list<string> $permissions
     */
    private function userWith(array $roles, array $permissions = []): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasRole', 'can'])
            ->getMock();

        $user->method('hasRole')->willReturnCallback(
            static fn (string $role): bool => in_array($role, $roles, true),
        );
        $user->method('can')->willReturnCallback(
            static fn (string $permission): bool => in_array($permission, $permissions, true),
        );

        return $user;
    }
}
