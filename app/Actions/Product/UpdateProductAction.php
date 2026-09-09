<?php

    namespace App\Actions\Product;

    use App\Models\Brand;
    use App\Models\Product;
    use App\Models\ProductCategory;
    use Illuminate\Support\Facades\DB;

    class UpdateProductAction {
        public function execute(Product $product, array $data): Product {
            return DB::transaction(function () use ($product, $data) {
                if (isset($data['brand_id'])) {
                    $data['brand_id'] = Brand::query()->where('public_id', $data['brand_id'])->value('id');
                }

                if (isset($data['product_category_id'])) {
                    $data['product_category_id'] = ProductCategory::query()
                        ->where('public_id', $data['product_category_id'])->value('id');
                }

                $product->fill($data);
                $product->save();

                return $product->fresh(Product::DEFAULT_RELATIONS);
            });
        }
    }
