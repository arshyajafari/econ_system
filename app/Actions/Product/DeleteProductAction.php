<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    /**
     * Remove a product from operational lists without destroying its history.
     *
     * Product uses SoftDeletes, so historical order items, samples, inventory
     * batches and visitor stock movements keep their foreign-key target. The
     * normal product queries automatically exclude the deleted product.
     */
    public function execute(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->id);

            // Do not delete batches or related rows: they are the audit trail
            // for orders, samples and inventory movements.
            $lockedProduct->delete();
        });
    }
}
