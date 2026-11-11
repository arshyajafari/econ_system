<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLocation;
use App\Models\EmployeeWorkSession;
use App\Http\Resources\EmployeeLocationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeWorkSessionController extends Controller {
    public function status(Request $request) {
        $employee = $this->employeeFor($request);
        $today = Carbon::now(config('app.timezone', 'Asia/Tehran'))->toDateString();

        $session = EmployeeWorkSession::query()
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($today) {
                $query->where('work_date', $today)->orWhereNull('ended_at');
            })
            ->orderByRaw('CASE WHEN ended_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('started_at')
            ->first();

        return response()->json(['data' => $this->serialize($session)]);
    }

    public function start(Request $request) {
        $employee = $this->employeeFor($request);
        $data = $this->validateCoordinates($request);
        $description = $request->validate(['description' => ['nullable', 'string', 'max:255']])['description'] ?? null;
        $operationId = $this->operationId($request);

        $session = DB::transaction(function () use ($employee, $data, $description, $operationId) {
            // Lock the employee row so concurrent taps/requests cannot create two sessions.
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $replayed = EmployeeWorkSession::query()
                ->where('start_operation_id', $operationId)
                ->first();
            if ($replayed) {
                if ((int) $replayed->employee_id !== (int) $employee->id
                    || abs((float) $replayed->start_latitude - (float) $data['latitude']) > 0.0000001
                    || abs((float) $replayed->start_longitude - (float) $data['longitude']) > 0.0000001) {
                    throw ValidationException::withMessages([
                        'client_operation_id' => ['شناسه این عملیات قبلاً برای اطلاعات دیگری استفاده شده است.'],
                    ]);
                }
                return $replayed;
            }

            $today = Carbon::now(config('app.timezone', 'Asia/Tehran'))->toDateString();
            $openSession = EmployeeWorkSession::query()
                ->where('employee_id', $employee->id)
                ->whereNull('ended_at')
                ->first();
            if ($openSession) {
                throw ValidationException::withMessages([
                    'work_session' => ['نوبت کاری قبلی هنوز پایان نیافته است؛ ابتدا پایان آن را ثبت کنید.'],
                ]);
            }

            return EmployeeWorkSession::query()->create([
                'employee_id' => $employee->id,
                'work_date' => $today,
                'description' => $description,
                'started_at' => now(),
                'start_latitude' => $data['latitude'],
                'start_longitude' => $data['longitude'],
                'start_accuracy' => $data['accuracy'] ?? null,
                'start_operation_id' => $operationId,
            ]);
        }, 3);

        return response()->json(['data' => $this->serialize($session)], 201);
    }

    public function end(Request $request) {
        $employee = $this->employeeFor($request);
        $data = $this->validateCoordinates($request);
        $operationId = $this->operationId($request);

        $session = DB::transaction(function () use ($employee, $data, $operationId) {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $replayed = EmployeeWorkSession::query()
                ->where('end_operation_id', $operationId)
                ->first();
            if ($replayed) {
                if ((int) $replayed->employee_id !== (int) $employee->id
                    || abs((float) $replayed->end_latitude - (float) $data['latitude']) > 0.0000001
                    || abs((float) $replayed->end_longitude - (float) $data['longitude']) > 0.0000001) {
                    throw ValidationException::withMessages([
                        'client_operation_id' => ['شناسه این عملیات قبلاً برای اطلاعات دیگری استفاده شده است.'],
                    ]);
                }
                return $replayed;
            }

            $session = EmployeeWorkSession::query()
                ->where('employee_id', $employee->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->lockForUpdate()
                ->first();

            if (!$session) {
                throw ValidationException::withMessages([
                    'work_session' => ['ابتدا باید شروع کار را ثبت کنید.'],
                ]);
            }
            if ($session->ended_at) {
                throw ValidationException::withMessages([
                    'work_session' => ['پایان کار امروز قبلاً ثبت شده است.'],
                ]);
            }

            $session->forceFill([
                'ended_at' => now(),
                'end_latitude' => $data['latitude'],
                'end_longitude' => $data['longitude'],
                'end_accuracy' => $data['accuracy'] ?? null,
                'end_operation_id' => $operationId,
            ])->save();

            return $session->refresh();
        }, 3);

        return response()->json(['data' => $this->serialize($session)]);
    }

    public function correct(Request $request, string $sessionId) {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'ended_at' => ['nullable', 'date'],
            'end_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'end_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'end_accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);
        if ((($data['end_latitude'] ?? null) !== null) xor (($data['end_longitude'] ?? null) !== null)) {
            throw ValidationException::withMessages([
                'end_latitude' => ['برای ثبت مختصات پایان، طول و عرض جغرافیایی باید با هم وارد شوند.'],
            ]);
        }

        $session = DB::transaction(function () use ($sessionId, $data, $request) {
            $session = EmployeeWorkSession::query()
                ->where('public_id', $sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->ended_at) {
                throw ValidationException::withMessages([
                    'work_session' => ['فقط مأموریت ناقص و پایان‌نیافته قابل اصلاح است.'],
                ]);
            }

            $timezone = config('app.timezone', 'Asia/Tehran');
            $endedAt = isset($data['ended_at'])
                ? Carbon::parse($data['ended_at'], $timezone)
                : Carbon::now($timezone);

            if ($endedAt->lt($session->started_at)) {
                throw ValidationException::withMessages([
                    'ended_at' => ['زمان پایان نمی‌تواند قبل از زمان شروع باشد.'],
                ]);
            }

            $session->forceFill([
                'ended_at' => $endedAt,
                'end_latitude' => $data['end_latitude'] ?? null,
                'end_longitude' => $data['end_longitude'] ?? null,
                'end_accuracy' => $data['end_accuracy'] ?? null,
                'admin_correction_reason' => $data['reason'],
                'admin_corrected_by' => $request->user()->id,
                'admin_corrected_at' => now($timezone),
            ])->save();

            return $session->refresh();
        }, 3);

        return response()->json(['data' => $this->serialize($session)]);
    }

    public function index(Request $request) {
        abort_unless($request->user()->hasRole('admin'), 403);
        $validated = $request->validate([
            'employee_id' => ['nullable', 'string', 'exists:employees,public_id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = EmployeeWorkSession::query()
            ->with('employee')
            ->when(
                $validated['employee_id'] ?? null,
                fn ($sessions, $employeeId) => $sessions->whereHas(
                    'employee',
                    fn ($employees) => $employees->where('public_id', $employeeId)
                )
            )
            ->orderByDesc('started_at')
            ->orderByDesc('id');

        $sessions = $query->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => $sessions->getCollection()->map(fn ($session) => $this->serialize($session))->values(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function locations(Request $request, string $sessionId)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $validated = $request->validate([
            'after' => ['nullable', 'date'],
        ]);

        $session = EmployeeWorkSession::query()
            ->where('public_id', $sessionId)
            ->firstOrFail();

        $query = EmployeeLocation::query()
            ->where('work_session_id', $session->id)
            ->with('employee');

        if (!empty($validated['after'])) {
            $query->where(
                'captured_at',
                '>=',
                Carbon::parse($validated['after'])->timezone(config('app.timezone', 'Asia/Tehran'))
            );
        }

        $locations = $query
            ->orderBy('captured_at')
            ->orderBy('id')
            ->limit(5000)
            ->get();

        return EmployeeLocationResource::collection($locations);
    }

    private function employeeFor(Request $request): Employee {
        abort_unless(!$request->user()->hasRole('accountant'), 403, 'حسابدار امکان ثبت شروع و پایان کار ندارد.');
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->status->value === 'active', 422, 'کاربر به کارمند فعال متصل نیست.');
        return $employee;
    }

    private function validateCoordinates(Request $request): array {
        return $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);
    }

    private function operationId(Request $request): string {
        return $request->validate([
            'client_operation_id' => ['required', 'uuid'],
        ])['client_operation_id'];
    }

    private function serialize(?EmployeeWorkSession $session): ?array {
        if (!$session) return null;

        return [
            'id' => $session->public_id,
            'employee_id' => $session->employee?->public_id,
            'employee_name' => $session->employee?->full_name,
            'work_date' => $session->work_date?->toDateString(),
            'description' => $session->description,
            'started_at' => $session->started_at?->toISOString(),
            'start' => [
                'latitude' => (float) $session->start_latitude,
                'longitude' => (float) $session->start_longitude,
                'accuracy' => $session->start_accuracy !== null ? (float) $session->start_accuracy : null,
            ],
            'ended_at' => $session->ended_at?->toISOString(),
            'end' => $session->ended_at && $session->end_latitude !== null && $session->end_longitude !== null ? [
                'latitude' => (float) $session->end_latitude,
                'longitude' => (float) $session->end_longitude,
                'accuracy' => $session->end_accuracy !== null ? (float) $session->end_accuracy : null,
            ] : null,
            'admin_corrected_at' => $session->admin_corrected_at?->toISOString(),
            'admin_correction_reason' => $session->admin_correction_reason,
            'status' => $session->ended_at ? 'completed' : 'in_progress',
            'duration_seconds' => $session->ended_at
                ? max(0, $session->started_at->diffInSeconds($session->ended_at, false))
                : max(0, $session->started_at->diffInSeconds(now(), false)),
        ];
    }
}
