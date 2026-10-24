<?php

namespace App\Actions\Product;

use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    public function execute(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->id);

            // Lock the product's batches before re-checking eligibility to reduce
            // the chance of a concurrent inventory operation racing this delete.
            $batches = InventoryBatch::withTrashed()
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->get();

            if (!$lockedProduct->canBeDeleted()) {
                throw new BusinessRuleException(
                    'این محصول در سفارش، نمونه ویزیت، موجودی ویزیتور علمی یا بچ دارای سابقه عملیاتی استفاده شده است و امکان حذف آن وجود ندارد.'
                );
            }

            // Remove eligible active batches first. Their movements remain
            // available as audit history and continue to resolve to soft-deleted batches.
            $batches->each(function (InventoryBatch $batch) {
                if (!$batch->trashed()) {
                    $batch->delete();
                }
            });

            $lockedProduct->delete();
        });
    }
}
