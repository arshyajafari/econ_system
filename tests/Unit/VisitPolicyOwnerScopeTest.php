<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\User;
use App\Models\Visit;
use App\Policies\VisitPolicy;
use PHPUnit\Framework\TestCase;

class VisitPolicyOwnerScopeTest extends TestCase
{
    public function test_scientific_visitor_can_update_own_visit(): void
    {
        $user = $this->scientificVisitor(employeeId: 17);
        $visit = $this->visit(employeeId: 17);

        $this->assertTrue((new VisitPolicy())->update($user, $visit));
    }

    public function test_scientific_visitor_cannot_view_or_update_another_employees_visit(): void
    {
        $user = $this->scientificVisitor(employeeId: 17);
        $visit = $this->visit(employeeId: 29);
        $policy = new VisitPolicy();

        $this->assertFalse($policy->view($user, $visit));
        $this->assertFalse($policy->update($user, $visit));
    }

    public function test_scientific_visitor_cannot_complete_or_cancel_another_employees_visit(): void
    {
        $user = $this->scientificVisitor(employeeId: 17);
        $visit = $this->visit(employeeId: 29);
        $policy = new VisitPolicy();

        $this->assertFalse($policy->complete($user, $visit));
        $this->assertFalse($policy->cancel($user, $visit));
    }

    public function test_scientific_visitor_permissions_are_respected(): void
    {
        $user = $this->scientificVisitor(employeeId: 17, permissions: []);
        $visit = $this->visit(employeeId: 17);
        $policy = new VisitPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->update($user, $visit));
        $this->assertFalse($policy->complete($user, $visit));
    }

    public function test_admin_can_access_another_employees_visit_with_permission(): void
    {
        $user = $this->userWith(['admin'], ['visits.view', 'visits.update']);
        $visit = $this->visit(employeeId: 29);
        $policy = new VisitPolicy();

        $this->assertTrue($policy->view($user, $visit));
        $this->assertTrue($policy->update($user, $visit));
    }

    /**
     * @param list<string> $roles
     * @param list<string> $permissions
     */
    private function userWith(array $roles, array $permissions, ?int $employeeId = null): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasRole', 'hasAnyRole', 'can'])
            ->getMock();

        $user->method('hasRole')->willReturnCallback(
            static fn (string $role): bool => in_array($role, $roles, true),
        );
        $user->method('hasAnyRole')->willReturnCallback(
            static fn (array|string $requestedRoles): bool =>
                count(array_intersect((array) $requestedRoles, $roles)) > 0,
        );
        $user->method('can')->willReturnCallback(
            static fn (string $permission): bool => in_array($permission, $permissions, true),
        );

        if ($employeeId !== null) {
            $employee = new Employee();
            $employee->setAttribute('id', $employeeId);
            $user->setRelation('employee', $employee);
        }

        return $user;
    }

    /**
     * @param list<string>|null $permissions
     */
    private function scientificVisitor(int $employeeId, ?array $permissions = null): User
    {
        return $this->userWith(
            ['scientific visitor'],
            $permissions ?? ['visits.view', 'visits.update', 'visits.complete', 'visits.cancel'],
            $employeeId,
        );
    }

    private function visit(int $employeeId): Visit
    {
        $visit = new Visit();
        $visit->setAttribute('employee_id', $employeeId);

        return $visit;
    }
}
