<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Brand\ChangeBrandActivityAction;
    use App\Actions\Brand\CreateBrandAction;
    use App\Actions\Brand\DeleteBrandAction;
    use App\Actions\Brand\ListBrandsAction;
    use App\Actions\Brand\ShowBrandAction;
    use App\Actions\Brand\UpdateBrandAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Brand\BrandIndexRequest;
    use App\Http\Requests\Brand\ChangeBrandActivityRequest;
    use App\Http\Requests\Brand\StoreBrandRequest;
    use App\Http\Requests\Brand\UpdateBrandRequest;
    use App\Http\Resources\BrandResource;
    use App\Models\Brand;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
    use Symfony\Component\HttpFoundation\Response;

    class BrandController extends Controller {
        public function __construct() {
            $this->authorizeModel(Brand::class, 'brand');
        }

        public function index(BrandIndexRequest $request, ListBrandsAction $action): AnonymousResourceCollection {
            return BrandResource::collection($action->execute($request->validated()));
        }

        public function show(Brand $brand, ShowBrandAction $action): BrandResource {
            return new BrandResource($action->execute($brand));
        }

        public function store(StoreBrandRequest $request, CreateBrandAction $action): BrandResource {
            return new BrandResource($action->execute($request->validated()));
        }

        public function update(UpdateBrandRequest $request, Brand $brand, UpdateBrandAction $action): BrandResource {
            return new BrandResource($action->execute($brand, $request->validated()));
        }

        public function destroy(Brand $brand, DeleteBrandAction $action): Response {
            $action->execute($brand);

            return response()->noContent();
        }

        public function changeActivity(ChangeBrandActivityRequest $request, Brand $brand,
            ChangeBrandActivityAction $action): BrandResource {
            return new BrandResource($action->execute($brand, $request->boolean('is_active')));
        }
    }
