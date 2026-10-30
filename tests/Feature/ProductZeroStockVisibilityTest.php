<?php

namespace Tests\Feature;

use App\Actions\Product\DeleteProductAction;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Queries\Product\ProductQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductZeroStockVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_stock_clock_starts_only_when_all_batches_are_empty(): void
    {
        $product = $this->createProduct();
        $firstBatch = $this->createBatch($product, 5);
        $secondBatch = $this->createBatch($product, 3);

        $firstBatch->quantity = 0;
        $firstBatch->save();

        $this->assertNull($product->fresh()->zero_stock_since);

        $secondBatch->quantity = 0;
        $secondBatch->save();

        $this->assertNotNull($product->fresh()->zero_stock_since);
    }

    public function test_restock_resets_zero_stock_clock_and_archive_state(): void
    {
        $product = $this->createProduct();
        $product->forceFill(['zero_stock_since' => now()->subDays(31)])->saveQuietly();
        $this->assertTrue($product->fresh()->isArchivedForNoStock());

        $batch = $this->createBatch($product, 4);
        $batch->save();

        $product->refresh();
        $this->assertNull($product->zero_stock_since);
        $this->assertFalse($product->isArchivedForNoStock());
    }

    public function test_non_admin_product_lists_exclude_zero_stock_products(): void
    {
        $stockedProduct = $this->createProduct();
        $emptyProduct = $this->createProduct();

        $this->createBatch($stockedProduct, 2);
        $this->createBatch($emptyProduct, 0);

        $results = ProductQuery::make()->apply([])->paginate(100);

        $this->assertSame(1, $results->total());
        $this->assertSame($stockedProduct->id, $results->first()->id);
    }


    public function test_used_product_can_be_removed_from_product_list_without_deleting_inventory_history(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, 5);

        app(DeleteProductAction::class)->execute($product);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('inventory_batches', ['id' => $batch->id, 'product_id' => $product->id]);
        $this->assertNotNull(Product::withTrashed()->findOrFail($product->id));
    }

    private function createProduct(): Product
    {
        $now = now();

        $brandId = DB::table('brands')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'BR-' . Str::upper(Str::random(8)),
            'title' => 'Zero stock test brand',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categoryId = DB::table('product_categories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CAT-' . Str::upper(Str::random(8)),
            'title' => 'Zero stock test category',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = DB::table('products')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'PRO-' . Str::upper(Str::random(8)),
            'brand_id' => $brandId,
            'product_category_id' => $categoryId,
            'title' => 'Zero stock test product',
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Product::query()->findOrFail($id);
    }

    private function createBatch(Product $product, int $quantity): InventoryBatch
    {
        $now = now();

        $id = DB::table('inventory_batches')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'product_id' => $product->id,
            'batch_number' => 'B-' . Str::upper(Str::random(8)),
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'purchase_price' => 100,
            'received_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return InventoryBatch::query()->findOrFail($id);
    }
}
