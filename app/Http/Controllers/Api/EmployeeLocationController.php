<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\Employee\\StoreEmployeeLocationRequest;
use App\\Http\\Resources\\EmployeeLocationResource;
use App\\Models\\Employee;
use App\\Models\\EmployeeLocation;
use Illuminate\\Http\\Request;

class EmployeeLocationController extends Controller {
    public function store(StoreEmployeeLocationRequest $request): EmployeeLocationResource {
        abort_unless(!$request->user()->hasRole('accountant'), 403, 'حسابدار نیازی به اشتراک موقعیت مکانی ندارد.');
        $employee = $request->user()->employee;
        abort_unless($employee && $employee->status->value === 'active', 422, 'کاربر به کارمند فعال متصل نیست.');

        $data = $request->validated();
        $data['employee_id'] = $employee->id;
        $data['captured_at'] = $data['captured_at'] ?? now();
        $data['source'] = $data['source'] ?? 'browser';

        $location = EmployeeLocation::create($data)->load('employee');
        return EmployeeLocationResource::make($location);
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

    public function history(Request $request) {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $employee = Employee::query()
            ->where('public_id', $validated['employee_id'])
            ->firstOrFail();

        $start = \\Illuminate\\Support\\Carbon::createFromFormat(
            '!Y-m-d',
            $validated['date'],
            config('app.timezone')
        )->startOfDay();
        $end = $start->copy()->endOfDay();

        $locations = EmployeeLocation::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('captured_at', [$start, $end])
            ->with('employee')
            ->orderBy('captured_at')
            ->limit(5000)
            ->get();

        return EmployeeLocationResource::collection($locations);
    }
}