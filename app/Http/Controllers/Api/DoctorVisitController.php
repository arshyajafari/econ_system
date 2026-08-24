<?php

    namespace App\Http\Controllers;

    use App\Actions\DoctorVisit\CreateDoctorVisitAction;
    use App\Actions\DoctorVisit\DeleteDoctorVisitAction;
    use App\Actions\DoctorVisit\UpdateDoctorVisitAction;
    use App\Http\Requests\DoctorVisit\DoctorVisitIndexRequest;
    use App\Http\Requests\DoctorVisit\StoreDoctorVisitRequest;
    use App\Http\Requests\DoctorVisit\UpdateDoctorVisitRequest;
    use App\Http\Resources\DoctorVisitResource;
    use App\Models\DoctorVisit;
    use App\Queries\Doctor\DoctorVisitQuery;
    use Illuminate\Http\JsonResponse;

    class DoctorVisitController extends Controller {
        public function __construct() {
            $this->authorizeModel(DoctorVisit::class, 'doctor_visit');
        }

        public function index(DoctorVisitIndexRequest $request, DoctorVisitQuery $query) {
            $visits = $query->apply($request->validated())->paginate($request->integer('per_page', 20));

            return DoctorVisitResource::collection($visits);
        }

        public function store(StoreDoctorVisitRequest $request, CreateDoctorVisitAction $action): JsonResponse {
            $visit = $action->execute($request->validated(), $request->user());

            return response()->json(new DoctorVisitResource($visit), 201);
        }

        public function show(DoctorVisit $doctorVisit): DoctorVisitResource {
            $doctorVisit->load(DoctorVisit::DEFAULT_RELATIONS);

            return new DoctorVisitResource($doctorVisit);
        }

        public function update(UpdateDoctorVisitRequest $request, DoctorVisit $doctorVisit,
            UpdateDoctorVisitAction $action): DoctorVisitResource {
            $this->authorize('update', $doctorVisit);

            $visit = $action->execute($doctorVisit, $request->validated());

            return new DoctorVisitResource($visit);
        }

        public function destroy(DoctorVisit $doctorVisit, DeleteDoctorVisitAction $action): JsonResponse {
            $this->authorize('delete', $doctorVisit);

            $action->execute($doctorVisit);

            return response()->json(null, 204);
        }
    }
