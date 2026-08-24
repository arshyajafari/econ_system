<?php

    namespace App\Actions\Product;

    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    class ChangeProductStatusAction {
        public function execute(Product $product, string $status): Product {
            return DB::transaction(function () use (
                $product, $status
            ) {
                if ($product->status === $status) {
                    return $product;
                }

                $product->update([
                    'status' => $status,
                ]);

                return $product->fresh(Product::DEFAULT_RELATIONS);
            });
        }
    }
