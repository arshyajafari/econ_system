<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeLocationRequest;
use App\Http\Resources\EmployeeLocationResource;
use App\Models\Employee;
use App\Models\EmployeeLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeLocationController extends Controller {
    public function store(StoreEmployeeLocationRequest $request): EmployeeLocationResource {
        $employee = $request->user()->employee;
        abort_unless($employee, 422, 'کاربر به کارمند فعال متصل نیست.');

        $data = $request->validated();
        $data['employee_id'] = $employee->id;
        $data['captured_at'] = $data['captured_at'] ?? now();
        $data['source'] = $data['source'] ?? 'browser';

        $location = EmployeeLocation::create($data)->load('employee');

        return EmployeeLocationResource::make($location);
    }

    public function index(Request $request) {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->active()
            ->with('latestLocation')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return EmployeeLocationResource::collection(
            $employees->map(fn(Employee $employee) => $employee->latestLocation)->filter()
        );
    }
}
