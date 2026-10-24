<?php

namespace Tests\Unit;

use App\Models\Delivery;
use App\Models\User;
use App\Policies\DeliveryPolicy;
use PHPUnit\Framework\TestCase;

class DeliveryPolicyViewPermissionTest extends TestCase
{
    public function test_delivery_operator_without_view_permission_cannot_list_or_view_deliveries(): void
    {
        $user = $this->userWith(['delivery operator']);
        $delivery = new Delivery();
        $policy = new DeliveryPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $delivery));
    }

    public function test_settlement_operator_with_view_permission_can_list_and_view_deliveries(): void
    {
        $user = $this->userWith(['settlement operator'], ['deliveries.view']);
        $delivery = new Delivery();
        $policy = new DeliveryPolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $delivery));
    }

    public function test_accountant_without_view_permission_cannot_list_deliveries(): void
    {
        $user = $this->userWith(['accountant']);

        $this->assertFalse((new DeliveryPolicy())->viewAny($user));
    }

    public function test_admin_override_is_preserved(): void
    {
        $user = $this->userWith(['admin']);

        $this->assertTrue((new DeliveryPolicy())->viewAny($user));
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
