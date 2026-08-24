<?php

    namespace App\Actions\Product;

    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    class DeleteProductAction {
        public function execute(Product $product): void {
            DB::transaction(function () use (
                $product
            ) {
                // این قسمت بعداً تکمیل می‌شود.

                // if ($product->orderItems()->exists()) {
                //     throw new BusinessException(...);
                // }

                // if ($product->inventoryItems()->exists()) {
                //     throw new BusinessException(...);
                // }

                $product->delete();
            });
        }
    }
