<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Doctor\ChangeDoctorStatusAction;
    use App\Actions\Doctor\CreateDoctorAction;
    use App\Actions\Doctor\DeleteDoctorAction;
    use App\Actions\Doctor\ListDoctorsAction;
    use App\Actions\Doctor\RestoreDoctorAction;
    use App\Actions\Doctor\ShowDoctorAction;
    use App\Actions\Doctor\UpdateDoctorAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Doctor\ChangeDoctorStatusRequest;
    use App\Http\Requests\Doctor\DoctorIndexRequest;
    use App\Http\Requests\Doctor\StoreDoctorRequest;
    use App\Http\Requests\Doctor\UpdateDoctorRequest;
    use App\Http\Resources\Doctor\DoctorCollection;
    use App\Http\Resources\DoctorResource;
    use App\Models\Doctor;
    use Illuminate\Http\JsonResponse;
    use Symfony\Component\HttpFoundation\Response;

    class DoctorController extends Controller {
        public function __construct() {
            $this->authorizeModel(Doctor::class, 'doctor');
        }

        public function index(DoctorIndexRequest $request, ListDoctorsAction $action): DoctorCollection {
            $this->authorize('viewAny', Doctor::class);

            return new DoctorCollection($action->execute($request->validated()));
        }

        public function store(StoreDoctorRequest $request, CreateDoctorAction $action): DoctorResource {
            $this->authorize('create', Doctor::class);

            return DoctorResource::make($action->execute($request->validated()));
        }

        public function show(Doctor $doctor, ShowDoctorAction $action): DoctorResource {
            $this->authorize('view', $doctor);

            return DoctorResource::make($action->execute($doctor));
        }

        public function update(UpdateDoctorRequest $request, Doctor $doctor,
            UpdateDoctorAction $action): DoctorResource {

            $this->authorize('update', $doctor);

            return DoctorResource::make($action->execute($doctor, $request->validated()));
        }

        public function destroy(Doctor $doctor, DeleteDoctorAction $action): JsonResponse {
            $this->authorize('delete', $doctor);

            $action->execute($doctor);

            return response()->json([], Response::HTTP_NO_CONTENT);
        }

        public function restore(Doctor $doctor, RestoreDoctorAction $action): DoctorResource {
            $this->authorize('restore', $doctor);

            return DoctorResource::make($action->execute($doctor));
        }

        public function changeStatus(ChangeDoctorStatusRequest $request, Doctor $doctor,
            ChangeDoctorStatusAction $action): DoctorResource {
            $this->authorize('changeStatus', $doctor);

            return DoctorResource::make($action->execute($doctor, $request->status()));
        }
    }
