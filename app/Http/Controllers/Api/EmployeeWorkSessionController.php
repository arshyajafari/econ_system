<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeWorkSession;
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
            'end_accuracy' => ['nullable', 'numeric', 'min:0', 'max:200'],
        ]);
        if (($data['end_latitude'] ?? null) !== null xor ($data['end_longitude'] ?? null) !== null) {
            throw ValidationException::withMessages([
                'end_latitude' => ['برای ثبت مختصات پایان، طول و عرض جغرافیایی باید با هم وارد شوند.'],
            ]);
        }

        $session = EmployeeWorkSession::query()->where('public_id', $sessionId)->firstOrFail();
        if ($session->ended_at) {
            throw ValidationException::withMessages([
                'work_session' => ['فقط مأموریت ناقص و پایان‌نیافته قابل اصلاح است.'],
            ]);
        }

        $endedAt = isset($data['ended_at'])
            ? Carbon::parse($data['ended_at'], config('app.timezone', 'Asia/Tehran'))
            : Carbon::now(config('app.timezone', 'Asia/Tehran'));
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
            'admin_corrected_at' => now(),
        ])->save();

        return response()->json(['data' => $this->serialize($session->refresh())]);
    }

    public function index(Request $request) {
        abort_unless($request->user()->hasRole('admin'), 403);
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date']
            ?? Carbon::now(config('app.timezone', 'Asia/Tehran'))->toDateString();

        $sessions = EmployeeWorkSession::query()
            ->with('employee')
            ->where('work_date', $date)
            ->orderBy('started_at')
            ->get();

        return response()->json(['data' => $sessions->map(fn ($session) => $this->serialize($session))]);
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
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:200'],
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
