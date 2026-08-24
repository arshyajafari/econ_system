<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Product\ChangeProductStatusAction;
    use App\Actions\Product\CreateProductAction;
    use App\Actions\Product\DeleteProductAction;
    use App\Actions\Product\ListProductsAction;
    use App\Actions\Product\ShowProductAction;
    use App\Actions\Product\UpdateProductAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Product\ChangeProductStatusRequest;
    use App\Http\Requests\Product\ProductIndexRequest;
    use App\Http\Requests\Product\StoreProductRequest;
    use App\Http\Requests\Product\UpdateProductRequest;
    use App\Http\Resources\ProductResource;
    use App\Models\Product;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
    use Symfony\Component\HttpFoundation\Response;

    class ProductController extends Controller {
        public function __construct() {
            $this->authorizeModel(Product::class, 'product');
        }

        public function index(ProductIndexRequest $request, ListProductsAction $action): AnonymousResourceCollection {
            return ProductResource::collection($action->execute($request->validated()));
        }

        public function show(Product $product, ShowProductAction $action): ProductResource {
            return new ProductResource($action->execute($product));
        }

        public function store(StoreProductRequest $request, CreateProductAction $action): ProductResource {
            return new ProductResource($action->execute($request->validated()));
        }

        public function update(UpdateProductRequest $request, Product $product,
            UpdateProductAction $action): ProductResource {
            return new ProductResource($action->execute($product, $request->validated()));
        }

        public function destroy(Product $product, DeleteProductAction $action): Response {
            $action->execute($product);

            return response()->noContent();
        }

        public function changeStatus(ChangeProductStatusRequest $request, Product $product,
            ChangeProductStatusAction $action): ProductResource {
            return new ProductResource($action->execute($product, $request->validated()['status']));
        }
    }
