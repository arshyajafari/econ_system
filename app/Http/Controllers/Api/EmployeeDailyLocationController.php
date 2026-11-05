<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Resources\\EmployeeLocationResource;
use App\\Models\\Employee;
use App\\Models\\EmployeeLocation;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Carbon;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;

class EmployeeDailyLocationController extends Controller
{
    public function today(Request $request)
    {
        $employee = $this->activeEmployee($request);
        $location = EmployeeLocation::query()
            ->where('employee_id', $employee->id)
            ->where('source', 'daily_checkin')
            ->whereBetween('captured_at', $this->todayRange())
            ->with('employee')
            ->latest('captured_at')
            ->first();

        return response()->json([
            'data' => $location ? (new EmployeeLocationResource($location))->resolve($request) : null,
        ]);
    }

    public function store(Request $request)
    {
        $employee = $this->activeEmployee($request);
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'client_operation_id' => ['required', 'uuid', 'max:64'],
        ]);

        [$start, $end] = $this->todayRange();
        $result = DB::transaction(function () use ($employee, $data, $start, $end) {
            // Serialize check-ins for the same employee so two rapid requests cannot
            // create two daily records, even when they use different idempotency keys.
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $byOperation = EmployeeLocation::query()
                ->where('client_operation_id', $data['client_operation_id'])
                ->first();

            if ($byOperation) {
                if ((int) $byOperation->employee_id !== (int) $employee->id
                    || $byOperation->source !== 'daily_checkin'
                    || abs((float) $byOperation->latitude - (float) $data['latitude']) > 0.0000001
                    || abs((float) $byOperation->longitude - (float) $data['longitude']) > 0.0000001) {
                    throw ValidationException::withMessages([
                        'client_operation_id' => ['شناسه ثبت قبلاً برای اطلاعات دیگری استفاده شده است.'],
                    ]);
                }

                return [$byOperation->load('employee'), false];
            }

            $existingToday = EmployeeLocation::query()
                ->where('employee_id', $employee->id)
                ->where('source', 'daily_checkin')
                ->whereBetween('captured_at', [$start, $end])
                ->with('employee')
                ->first();

            if ($existingToday) {
                return [$existingToday, false];
            }

            $location = EmployeeLocation::query()->create([
                'employee_id' => $employee->id,
                'work_session_id' => null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'source' => 'daily_checkin',
                'captured_at' => now(),
                'client_operation_id' => $data['client_operation_id'],
            ]);

            return [$location->load('employee'), true];
        }, 3);

        return response()->json([
            'data' => (new EmployeeLocationResource($result[0]))->resolve($request),
        ], $result[1] ? 201 : 200);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $timezone = config('app.timezone', 'Asia/Tehran');
        $date = $validated['date'] ?? Carbon::now($timezone)->toDateString();
        $start = Carbon::createFromFormat('!Y-m-d', $date, $timezone)->startOfDay();
        $end = $start->copy()->endOfDay();

        $locations = EmployeeLocation::query()
            ->where('source', 'daily_checkin')
            ->whereBetween('captured_at', [$start, $end])
            ->with('employee')
            ->orderByDesc('captured_at')
            ->limit(2000)
            ->get();

        return EmployeeLocationResource::collection($locations);
    }

    private function activeEmployee(Request $request): Employee
    {
        abort_unless(!$request->user()->hasRole('accountant'), 403, 'حسابدار امکان ثبت موقعیت را ندارد.');

        $employee = $request->user()->employee;
        abort_unless($employee && $employee->status->value === 'active', 422, 'کاربر به کارمند فعال متصل نیست.');

        return $employee;
    }

    private function todayRange(): array
    {
        $timezone = config('app.timezone', 'Asia/Tehran');
        $start = Carbon::now($timezone)->startOfDay();

        return [$start, $start->copy()->endOfDay()];
    }
}
