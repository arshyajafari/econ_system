<?php

namespace App\Services;

use App\Enums\EmployeeActivityType;
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
        return match ($targetType) {
            'all' => User::query()
                ->active()
                ->get(),

            'users' => User::query()
                ->active()
                ->whereIn('public_id', array_values(array_unique($userIds)))
                ->get(),

            'positions' => User::query()
                ->active()
                ->whereNotNull('employee_id')
                ->whereHas('employee', function ($query) use ($positionTypes): void {
                    $query
                        ->whereIn('activity_type', array_values(array_unique($positionTypes)))
                        ->where('status', 'active');
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
