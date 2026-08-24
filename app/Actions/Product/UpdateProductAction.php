<?php

    namespace App\Actions\Product;

    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    class UpdateProductAction {
        public function execute(Product $product, array $data): Product {
            return DB::transaction(function () use (
                $product, $data
            ) {
                $product->fill($data);
                $product->save();

                return $product->fresh(Product::DEFAULT_RELATIONS);
            });
        }
    }
