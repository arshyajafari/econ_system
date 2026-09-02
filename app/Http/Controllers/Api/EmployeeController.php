<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Employee\ChangeEmployeeStatusAction;
    use App\Actions\Employee\DeleteEmployeeAction;
    use App\Actions\Employee\ListEmployeesAction;
    use App\Actions\Employee\RestoreEmployeeAction;
    use App\Actions\Employee\ShowEmployeeAction;
    use App\Actions\Employee\UpdateEmployeeAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Employee\ChangeEmployeeStatusRequest;
    use App\Http\Requests\Employee\EmployeeIndexRequest;
    use App\Http\Requests\Employee\StoreEmployeeRequest;
    use App\Http\Requests\Employee\UpdateEmployeeRequest;
    use App\Http\Resources\EmployeeResource;
    use App\Models\Employee;

    class EmployeeController extends Controller {
        public function __construct() {
            $this->authorizeModel(Employee::class, 'employee');
        }

        public function index(EmployeeIndexRequest $request, ListEmployeesAction $action) {
            $this->authorize('viewAny', Employee::class);

            return EmployeeResource::collection($action->execute($request->filters()));
        }

        public function store(StoreEmployeeRequest $request, StoreEmployeeRequest $action): EmployeeResource {
            $this->authorize('create', Employee::class);

            return EmployeeResource::make($action->execute($request->validated()));
        }

        public function show(Employee $employee, ShowEmployeeAction $action): EmployeeResource {
            $this->authorize('view', $employee);

            return EmployeeResource::make($action->execute($employee));
        }

        public function update(UpdateEmployeeRequest $request, Employee $employee,
            UpdateEmployeeAction $action): EmployeeResource {
            $this->authorize('update', $employee);

            return EmployeeResource::make($action->execute($employee, $request->validated()));
        }

        public function destroy(Employee $employee, DeleteEmployeeAction $action) {
            $this->authorize('delete', $employee);

            $action->execute($employee);

            return response()->noContent();
        }

        public function restore(Employee $employee, RestoreEmployeeAction $action): EmployeeResource {
            $this->authorize('restore', $employee);

            return EmployeeResource::make($action->execute($employee));
        }

        public function changeStatus(ChangeEmployeeStatusRequest $request, Employee $employee,
            ChangeEmployeeStatusAction $action): EmployeeResource {
            $this->authorize('changeStatus', $employee);

            return EmployeeResource::make($action->execute($employee, $request->validated('status')));
        }
    }
