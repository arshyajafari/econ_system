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

    public function test_only_admin_can_create_or_update_delivery_records(): void
    {
        $policy = new DeliveryPolicy();
        $delivery = new Delivery();

        $admin = $this->userWith(['admin'], ['deliveries.create', 'deliveries.update']);
        $operator = $this->userWith(['delivery operator'], ['deliveries.create', 'deliveries.update']);

        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $delivery));
        $this->assertFalse($policy->create($operator));
        $this->assertFalse($policy->update($operator, $delivery));
    }

    public function test_only_admin_can_delete_delivery(): void
    {
        $policy = new DeliveryPolicy();
        $delivery = new Delivery();

        $admin = $this->userWith(['admin'], ['deliveries.delete']);
        $accountant = $this->userWith(['accountant'], ['deliveries.delete']);

        $this->assertTrue($policy->delete($admin, $delivery));
        $this->assertFalse($policy->delete($accountant, $delivery));
    }

    public function test_admin_and_delivery_operator_with_permission_can_confirm_physical_handoff(): void
    {
        $policy = new DeliveryPolicy();
        $delivery = new Delivery();

        $admin = $this->userWith(['admin'], ['deliveries.complete']);
        $adminWithoutPermission = $this->userWith(['admin']);
        $operator = $this->userWith(['delivery operator'], ['deliveries.complete']);
        $accountant = $this->userWith(['accountant'], ['deliveries.complete']);

        $this->assertTrue($policy->complete($admin, $delivery));
        $this->assertFalse($policy->complete($adminWithoutPermission, $delivery));
        $this->assertTrue($policy->complete($operator, $delivery));
        $this->assertFalse($policy->complete($accountant, $delivery));
    }

    public function test_only_admin_can_cancel_delivery(): void
    {
        $policy = new DeliveryPolicy();
        $delivery = new Delivery();

        $admin = $this->userWith(['admin'], ['deliveries.cancel']);
        $accountant = $this->userWith(['accountant'], ['deliveries.cancel']);

        $this->assertTrue($policy->cancel($admin, $delivery));
        $this->assertFalse($policy->cancel($accountant, $delivery));
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
