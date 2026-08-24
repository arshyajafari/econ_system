<?php

    namespace App\Http\Controllers;

    use App\Actions\Sample\CreateSampleAction;
    use App\Actions\Sample\DeleteSampleAction;
    use App\Actions\Sample\ListSamplesAction;
    use App\Actions\Sample\ShowSampleAction;
    use App\Actions\Sample\UpdateSampleAction;
    use App\Http\Requests\Sample\SampleIndexRequest;
    use App\Http\Requests\Sample\StoreSampleRequest;
    use App\Http\Requests\Sample\UpdateSampleRequest;
    use App\Http\Resources\SampleResource;
    use App\Models\Sample;
    use Illuminate\Http\JsonResponse;

    class SampleController extends Controller {
        public function __construct() {
            $this->authorizeModel(Sample::class, 'sample');
        }

        public function index(SampleIndexRequest $request, ListSamplesAction $action) {
            $samples = $action->execute($request->validated());

            return SampleResource::collection($samples);
        }

        public function store(StoreSampleRequest $request, CreateSampleAction $action): JsonResponse {
            $sample = $action->execute($request->validated(), $request->user());

            return response()->json(new SampleResource($sample), 201);
        }

        public function show(Sample $sample, ShowSampleAction $action): SampleResource {
            return new SampleResource($action->execute($sample));
        }

        public function update(UpdateSampleRequest $request, Sample $sample,
            UpdateSampleAction $action): SampleResource {
            $this->authorize('update', $sample);

            return new SampleResource($action->execute($sample, $request->validated()));
        }

        public function destroy(Sample $sample, DeleteSampleAction $action): JsonResponse {
            $this->authorize('delete', $sample);

            $action->execute($sample);

            return response()->json(null, 204);
        }
    }
