<?php

    namespace App\Actions\Product;

    use App\Models\Product;

    class ShowProductAction {
        public function execute(Product $product): Product {
            return $product->fresh(Product::DEFAULT_RELATIONS);
        }
    }
