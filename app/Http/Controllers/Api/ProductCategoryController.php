<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\ProductCategory\ChangeProductCategoryActivityAction;
    use App\Actions\ProductCategory\CreateProductCategoryAction;
    use App\Actions\ProductCategory\DeleteProductCategoryAction;
    use App\Actions\ProductCategory\ListProductCategoriesAction;
    use App\Actions\ProductCategory\ListProductCategoriesTreeAction;
    use App\Actions\ProductCategory\ShowProductCategoryAction;
    use App\Actions\ProductCategory\UpdateProductCategoryAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\ProductCategory\ChangePCActivityRequest;
    use App\Http\Requests\ProductCategory\ProductCategoryIndexRequest;
    use App\Http\Requests\ProductCategory\StoreProductCategoryRequest;
    use App\Http\Requests\ProductCategory\UpdateProductCategoryRequest;
    use App\Http\Resources\ProductCategoryResource;
    use App\Models\ProductCategory;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
    use Symfony\Component\HttpFoundation\Response;

    class ProductCategoryController extends Controller {
        public function __construct() {
            $this->authorizeModel(ProductCategory::class, 'product_category');
        }

        public function index(ProductCategoryIndexRequest $request,
            ListProductCategoriesAction $action): AnonymousResourceCollection {
            return ProductCategoryResource::collection($action->execute($request->validated()));
        }

        public function tree(ListProductCategoriesTreeAction $action): AnonymousResourceCollection {
            return ProductCategoryResource::collection($action->execute());
        }

        public function show(ProductCategory $productCategory,
            ShowProductCategoryAction $action): ProductCategoryResource {
            return new ProductCategoryResource($action->execute($productCategory));
        }

        public function store(StoreProductCategoryRequest $request,
            CreateProductCategoryAction $action): ProductCategoryResource {
            return new ProductCategoryResource($action->execute($request->validated()));
        }

        public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory,
            UpdateProductCategoryAction $action): ProductCategoryResource {
            return new ProductCategoryResource($action->execute($productCategory, $request->validated()));
        }

        public function destroy(ProductCategory $productCategory, DeleteProductCategoryAction $action): Response {
            $action->execute($productCategory);

            return response()->noContent();
        }

        public function changeActivity(ChangePCActivityRequest $request, ProductCategory $productCategory,
            ChangeProductCategoryActivityAction $action): ProductCategoryResource {
            return new ProductCategoryResource($action->execute($productCategory, $request->boolean('is_active')));
        }
    }
