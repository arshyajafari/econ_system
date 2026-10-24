<?php

namespace Tests\Feature;

use App\Actions\Inventory\InventoryBatch\UpdateInventoryBatchAction;
use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpdateInventoryBatchMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_batch_expiry_cannot_be_changed_to_make_it_sellable_again(): void
    {
        $product = $this->createProduct();
        $expiredDate = today()->subDay()->toDateString();
        $batch = $this->createBatch($product, 'B-EXPIRED', $expiredDate);
        $this->createMovement($batch, 'order_fulfillment');

        try {
            app(UpdateInventoryBatchAction::class)->execute($batch, [
                'expire_date' => today()->addYear()->toDateString(),
            ]);
            $this->fail('A batch with operational history must not have its expiry changed.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseHas('inventory_batches', [
                'id' => $batch->id,
                'expire_date' => $expiredDate,
            ]);
        }
    }

    public function test_unused_batch_identity_can_be_corrected_when_it_has_only_initial_receipt_history(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, 'B-OLD', null);
        $this->createMovement($batch, 'inventory_receipt');

        $updated = app(UpdateInventoryBatchAction::class)->execute($batch, [
            'batch_number' => 'B-CORRECTED',
            'expire_date' => today()->addMonths(6)->toDateString(),
        ]);

        $this->assertSame('B-CORRECTED', $updated->batch_number);
        $this->assertSame(today()->addMonths(6)->toDateString(), $updated->expire_date?->toDateString());
    }

    public function test_batch_description_can_be_updated_after_operational_use(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, 'B-USED', today()->addMonths(2)->toDateString());
        $this->createMovement($batch, 'order_fulfillment');

        $updated = app(UpdateInventoryBatchAction::class)->execute($batch, [
            'description' => 'Verified storage note',
        ]);

        $this->assertSame('Verified storage note', $updated->description);
        $this->assertSame('B-USED', $updated->batch_number);
    }

    public function test_batch_identity_update_cannot_create_duplicate_product_batch_expiry_combination(): void
    {
        $product = $this->createProduct();
        $expiry = today()->addMonths(4)->toDateString();
        $this->createBatch($product, 'B-EXISTING', $expiry);
        $target = $this->createBatch($product, 'B-TARGET', $expiry);

        try {
            app(UpdateInventoryBatchAction::class)->execute($target, [
                'batch_number' => 'B-EXISTING',
            ]);
            $this->fail('Duplicate batch identities must be rejected.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseHas('inventory_batches', [
                'id' => $target->id,
                'batch_number' => 'B-TARGET',
            ]);
        }
    }

    private function createProduct(): Product
    {
        $now = now();

        $brandId = DB::table('brands')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'BR-' . Str::upper(Str::random(8)),
            'title' => 'Batch metadata test brand',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categoryId = DB::table('product_categories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CAT-' . Str::upper(Str::random(8)),
            'title' => 'Batch metadata test category',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'PRO-' . Str::upper(Str::random(8)),
            'brand_id' => $brandId,
            'product_category_id' => $categoryId,
            'title' => 'Batch metadata test product',
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Product::query()->findOrFail($productId);
    }

    private function createBatch(Product $product, string $batchNumber, ?string $expireDate): InventoryBatch
    {
        $now = now();

        $batchId = DB::table('inventory_batches')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'product_id' => $product->id,
            'batch_number' => $batchNumber,
            'expire_date' => $expireDate,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'purchase_price' => 100,
            'received_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return InventoryBatch::query()->findOrFail($batchId);
    }

    private function createMovement(InventoryBatch $batch, string $reason): void
    {
        $now = now();

        DB::table('inventory_movements')->insert([
            'public_id' => (string) Str::ulid(),
            'inventory_batch_id' => $batch->id,
            'type' => 'out',
            'quantity' => 1,
            'reason' => $reason,
            'moved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
