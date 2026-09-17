<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\DB;

class SetProductPriceAction
{
    public function execute(Product $product, array $data): ProductPrice
    {
        return DB::transaction(function () use ($product, $data) {
            $effectiveFrom = isset($data['effective_from']) ? now()->parse($data['effective_from']) : now();

            $product->prices()
                ->where('is_active', true)
                ->whereNull('effective_to')
                ->update([
                    'effective_to' => $effectiveFrom,
                    'is_active' => false,
                ]);

            return $product->prices()->create([
                'sale_price' => $data['sale_price'],
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'is_active' => true,
                'description' => $data['description'] ?? null,
            ]);
        });
    }
}
