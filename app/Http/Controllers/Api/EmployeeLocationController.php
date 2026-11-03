<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeLocationRequest;
use App\Http\Resources\EmployeeLocationResource;
use App\Models\Employee;
use App\Models\EmployeeLocation;
use App\Models\EmployeeWorkSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeLocationController extends Controller {
    private const MAX_GAP_MS = 300_000;
    private const STOP_GAP_MS = 180_000;
    private const STOP_RADIUS_METERS = 50;
    private const MIN_STOP_MS = 300_000;
    private const MAX_SPEED_KMH = 180;
    private const DAILY_POINT_LIMIT = 5000;

    public function store(StoreEmployeeLocationRequest $request): EmployeeLocationResource
    {
        $user = $request->user();
        abort_unless(!$user->hasRole('accountant'), 403, 'حسابدار امکان ثبت موقعیت مأموریت را ندارد.');

        $employee = $user->employee;
        abort_unless($employee && $employee->status->value === 'active', 422, 'کاربر به کارمند فعال متصل نیست.');

        $data = $request->validated();
        $operationId = $data['client_operation_id'] ?? null;

        if ($operationId) {
            $existing = EmployeeLocation::query()
                ->where('client_operation_id', $operationId)
                ->first();

            if ($existing) {
                return EmployeeLocationResource::make(
                    $this->assertLocationOperationMatches($existing, $employee, $data)->load('employee')
                );
            }
        }

        $location = DB::transaction(function () use ($employee, $data, $operationId) {
            $session = EmployeeWorkSession::query()
                ->where('employee_id', $employee->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->lockForUpdate()
                ->first();

            if (!$session) {
                throw ValidationException::withMessages([
                    'work_session' => ['برای ثبت مسیر باید ابتدا مأموریت را شروع کنید.'],
                ]);
            }

            // Re-check the idempotency key after acquiring the session lock so
            // concurrent retries cannot insert duplicate location samples.
            if ($operationId) {
                $existing = EmployeeLocation::query()
                    ->where('client_operation_id', $operationId)
                    ->first();

                if ($existing) {
                    return $this->assertLocationOperationMatches($existing, $employee, $data);
                }
            }

            return EmployeeLocation::query()->create([
                'employee_id' => $employee->id,
                'work_session_id' => $session->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'source' => 'work_session',
                'captured_at' => now(),
                'client_operation_id' => $operationId,
                'meta' => $data['meta'] ?? null,
            ])->load('employee');
        }, 3);

        return EmployeeLocationResource::make($location->load('employee'));
    }

    private function assertLocationOperationMatches(
        EmployeeLocation $existing,
        Employee $employee,
        array $data
    ): EmployeeLocation {
        if (
            (int) $existing->employee_id !== (int) $employee->id
            || abs((float) $existing->latitude - (float) $data['latitude']) > 0.0000001
            || abs((float) $existing->longitude - (float) $data['longitude']) > 0.0000001
        ) {
            throw ValidationException::withMessages([
                'client_operation_id' => ['شناسه این نقطه قبلاً برای اطلاعات دیگری استفاده شده است.'],
            ]);
        }

        return $existing;
    }

    public function index(Request $request) {
        abort_unless($request->user()->hasRole('admin'), 403);

        $employees = Employee::query()
            ->active()
            ->with('latestLocation.employee')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $locations = $employees->map(fn (Employee $employee) => $employee->latestLocation)
            ->filter()
            ->values();

        return EmployeeLocationResource::collection($locations);
    }

    public function live(Request $request) {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'after' => ['nullable', 'date'],
        ]);

        $employee = Employee::query()
            ->where('public_id', $validated['employee_id'])
            ->firstOrFail();

        $timezone = config('app.timezone', 'Asia/Tehran');
        $query = EmployeeLocation::query()
            ->where('employee_id', $employee->id)
            ->with('employee');

        if (!empty($validated['after'])) {
            // Include the boundary timestamp; the client de-duplicates by public ID
            // so points sharing the same timestamp are not lost between polls.
            $query->where('captured_at', '>=', Carbon::parse($validated['after'])->timezone($timezone));
        } else {
            $query->whereBetween('captured_at', [
                Carbon::now($timezone)->startOfDay(),
                Carbon::now($timezone)->endOfDay(),
            ]);
        }

        $locations = $query
            ->orderBy('captured_at')
            ->orderBy('id')
            ->limit(self::DAILY_POINT_LIMIT)
            ->get();

        return EmployeeLocationResource::collection($locations);
    }

    public function history(Request $request) {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $employee = Employee::query()
            ->where('public_id', $validated['employee_id'])
            ->firstOrFail();

        $timezone = config('app.timezone', 'Asia/Tehran');
        $start = Carbon::createFromFormat('!Y-m-d', $validated['date'], $timezone)->startOfDay();
        $end = $start->copy()->endOfDay();

        $locations = EmployeeLocation::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('captured_at', [$start, $end])
            ->with('employee')
            ->orderBy('captured_at')
            ->limit(self::DAILY_POINT_LIMIT)
            ->get();

        return EmployeeLocationResource::collection($locations);
    }

    public function dailySummary(Request $request) {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
        ]);

        $employee = Employee::query()
            ->where('public_id', $validated['employee_id'])
            ->firstOrFail();

        $timezone = config('app.timezone', 'Asia/Tehran');
        $today = Carbon::now($timezone)->startOfDay();
        $start = $today->copy()->subDays(29);
        $end = $today->copy()->endOfDay();

        $daily = [];
        $currentDate = null;
        $dayPoints = [];

        $flushDay = function () use (&$daily, &$dayPoints, &$currentDate): void {
            if ($currentDate === null || $dayPoints === []) {
                $dayPoints = [];
                return;
            }

            $meters = 0.0;
            $gaps = 0;
            for ($index = 1, $count = count($dayPoints); $index < $count; $index++) {
                $gap = $dayPoints[$index]['timestamp'] - $dayPoints[$index - 1]['timestamp'];
                $distance = $this->distanceMeters($dayPoints[$index - 1], $dayPoints[$index]);
                $speed = $gap > 0 ? $distance / $gap * 3600 : INF;

                if ($gap > self::MAX_GAP_MS || $gap <= 0 || $speed > self::MAX_SPEED_KMH) {
                    $gaps++;
                    continue;
                }

                $meters += $distance;
            }

            $stops = $this->findStops($dayPoints);
            $stopDurationMs = array_sum(array_column($stops, 'duration_ms'));

            $daily[] = [
                'date' => $currentDate,
                'points_count' => count($dayPoints),
                'distance_meters' => round($meters, 1),
                'stop_duration_seconds' => (int) round($stopDurationMs / 1000),
                'stops_count' => count($stops),
                'gaps_count' => $gaps,
            ];

            $dayPoints = [];
        };

        $locations = EmployeeLocation::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('captured_at', [$start, $end])
            ->orderBy('captured_at')
            ->orderBy('id')
            ->cursor();

        foreach ($locations as $location) {
            $capturedAt = $location->captured_at;
            if (!$capturedAt) {
                continue;
            }

            $date = $capturedAt->copy()->timezone($timezone)->format('Y-m-d');
            if ($date !== $currentDate) {
                $flushDay();
                $currentDate = $date;
            }

            // Match the daily map endpoint's 5,000-point cap to keep the report bounded.
            if (count($dayPoints) >= self::DAILY_POINT_LIMIT) {
                continue;
            }

            $dayPoints[] = [
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'timestamp' => $capturedAt->getTimestamp() * 1000,
            ];
        }

        $flushDay();

        return response()->json($daily);
    }

    private function distanceMeters(array $a, array $b): float {
        $radians = static fn (float $degrees): float => $degrees * M_PI / 180;
        $latitudeDelta = $radians($b['latitude'] - $a['latitude']);
        $longitudeDelta = $radians($b['longitude'] - $a['longitude']);
        $value = sin($latitudeDelta / 2) ** 2
            + cos($radians($a['latitude'])) * cos($radians($b['latitude']))
            * sin($longitudeDelta / 2) ** 2;

        return 6_371_000 * 2 * atan2(sqrt($value), sqrt(max(0, 1 - $value)));
    }

    private function findStops(array $points): array {
        $stops = [];
        $anchorIndex = 0;
        $lastInRangeIndex = 0;
        $count = count($points);

        $saveStop = function () use (&$stops, &$points, &$anchorIndex, &$lastInRangeIndex): void {
            $start = $points[$anchorIndex]['timestamp'];
            $end = $points[$lastInRangeIndex]['timestamp'];
            if ($end - $start >= self::MIN_STOP_MS) {
                $stops[] = ['duration_ms' => $end - $start];
            }
        };

        for ($index = 1; $index < $count; $index++) {
            $gap = $points[$index]['timestamp'] - $points[$lastInRangeIndex]['timestamp'];
            $withinRadius = $this->distanceMeters($points[$anchorIndex], $points[$index]) <= self::STOP_RADIUS_METERS;
            if ($gap >= 0 && $gap <= self::STOP_GAP_MS && $withinRadius) {
                $lastInRangeIndex = $index;
                continue;
            }

            $saveStop();
            $anchorIndex = $index;
            $lastInRangeIndex = $index;
        }

        if ($count > 1) {
            $saveStop();
        }

        return $stops;
    }
}
