<?php

namespace App\Http\Controllers\Api;

use App\Actions\Product\ChangeProductStatusAction;
use App\Actions\Product\CreateProductAction;
use App\Actions\Product\DeleteProductAction;
use App\Actions\Product\ListProductsAction;
use App\Actions\Product\SetProductPriceAction;
use App\Actions\Product\ShowProductAction;
use App\Actions\Product\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ChangeProductStatusRequest;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Http\Requests\Product\SetProductPriceRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UploadProductImageRequest;
use App\Http\Resources\ProductPriceResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct() { $this->authorizeModel(Product::class, 'product'); }

    public function index(ProductIndexRequest $request, ListProductsAction $action): AnonymousResourceCollection
    { return ProductResource::collection($action->execute($request->validated())); }

    public function show(Product $product, ShowProductAction $action): ProductResource
    { return new ProductResource($action->execute($product)); }

    public function store(StoreProductRequest $request, CreateProductAction $action): ProductResource
    { return new ProductResource($action->execute($request->validated())); }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): ProductResource
    { return new ProductResource($action->execute($product, $request->validated())); }

    public function setPrice(SetProductPriceRequest $request, Product $product, SetProductPriceAction $action): ProductPriceResource
    {
        $this->authorize('update', $product);
        return new ProductPriceResource($action->execute($product, $request->validated()));
    }

    public function uploadImage(UploadProductImageRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);
        $oldImage = $product->image;
        $newImage = $request->file('image')->store('products', 'public');
        $product->update(['image' => $newImage]);
        if ($oldImage && Storage::disk('public')->exists($oldImage)) Storage::disk('public')->delete($oldImage);
        return new ProductResource($product->fresh(Product::DEFAULT_RELATIONS));
    }

    public function destroy(Product $product, DeleteProductAction $action): Response
    { $action->execute($product); return response()->noContent(); }

    public function changeStatus(ChangeProductStatusRequest $request, Product $product, ChangeProductStatusAction $action): ProductResource
    {
        $this->authorize('changeStatus', $product);
        return new ProductResource($action->execute($product, $request->validated()['status']));
    }
}
