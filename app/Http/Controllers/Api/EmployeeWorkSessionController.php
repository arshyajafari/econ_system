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
            ->where('work_date', $today)
            ->first();

        return response()->json(['data' => $this->serialize($session)]);
    }

    public function start(Request $request) {
        $employee = $this->employeeFor($request);
        $data = $this->validateCoordinates($request);
        $operationId = $this->operationId($request);

        $session = DB::transaction(function () use ($employee, $data, $operationId) {
            // Lock the employee row so concurrent taps/requests cannot create two sessions.
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $replayed = EmployeeWorkSession::query()
                ->where('employee_id', $employee->id)
                ->where('start_operation_id', $operationId)
                ->first();
            if ($replayed) {
                return $replayed;
            }

            $today = Carbon::now(config('app.timezone', 'Asia/Tehran'))->toDateString();
            $existing = EmployeeWorkSession::query()
                ->where('employee_id', $employee->id)
                ->where('work_date', $today)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'work_session' => [$existing->ended_at
                        ? 'برای امروز یک نوبت کاری ثبت شده و پایان یافته است.'
                        : 'شروع کار امروز قبلاً ثبت شده است.'],
                ]);
            }

            return EmployeeWorkSession::query()->create([
                'employee_id' => $employee->id,
                'work_date' => $today,
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
                ->where('employee_id', $employee->id)
                ->where('end_operation_id', $operationId)
                ->first();
            if ($replayed) {
                return $replayed;
            }

            $today = Carbon::now(config('app.timezone', 'Asia/Tehran'))->toDateString();
            $session = EmployeeWorkSession::query()
                ->where('employee_id', $employee->id)
                ->where('work_date', $today)
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
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);
    }

    private function operationId(Request $request): string {
        return $request->validate([
            'client_operation_id' => ['required', 'string', 'min:16', 'max:64'],
        ])['client_operation_id'];
    }

    private function serialize(?EmployeeWorkSession $session): ?array {
        if (!$session) return null;

        return [
            'id' => $session->public_id,
            'employee_id' => $session->employee?->public_id,
            'employee_name' => $session->employee?->full_name,
            'work_date' => $session->work_date?->toDateString(),
            'started_at' => $session->started_at?->toISOString(),
            'start' => [
                'latitude' => (float) $session->start_latitude,
                'longitude' => (float) $session->start_longitude,
                'accuracy' => $session->start_accuracy !== null ? (float) $session->start_accuracy : null,
            ],
            'ended_at' => $session->ended_at?->toISOString(),
            'end' => $session->ended_at ? [
                'latitude' => (float) $session->end_latitude,
                'longitude' => (float) $session->end_longitude,
                'accuracy' => $session->end_accuracy !== null ? (float) $session->end_accuracy : null,
            ] : null,
            'status' => $session->ended_at ? 'completed' : 'in_progress',
        ];
    }
}
