<?php

namespace App\Services;

use App\Enums\EmployeeActivityType;
use App\Enums\EmployeeStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationRecipientResolver
{
    /**
     * Resolve the exact active users that should receive a system message.
     *
     * Job-position targeting is deliberately based on Employee::activity_type,
     * never on Spatie authorization roles. This keeps authorization and job
     * position semantics completely separate.
     *
     * @param array<int, string> $positionTypes
     * @param array<int, string> $userIds
     */
    public function resolve(
        string $targetType,
        array $userIds = [],
        array $positionTypes = [],
    ): Collection {
        $query = User::query()
            ->active()
            ->with('employee:id,public_id,first_name,last_name,status,activity_type');

        return match ($targetType) {
            'all' => $query->get(),

            'users' => $query
                ->whereIn('public_id', array_values(array_unique($userIds)))
                ->get(),

            'positions' => $query
                ->whereNotNull('employee_id')
                ->whereHas('employee', function ($employeeQuery) use ($positionTypes): void {
                    $employeeQuery
                        ->whereIn('activity_type', array_values(array_unique($positionTypes)))
                        ->where('status', EmployeeStatus::ACTIVE->value);
                })
                ->get(),

            default => new Collection(),
        };
    }

    /**
     * Return the canonical position values accepted by the application.
     *
     * @return array<int, string>
     */
    public function normalizePositions(array $positionTypes): array
    {
        $allowed = array_map(
            static fn (EmployeeActivityType $position): string => $position->value,
            EmployeeActivityType::cases(),
        );

        return array_values(array_intersect(array_unique($positionTypes), $allowed));
    }
}
