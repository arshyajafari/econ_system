<?php

namespace Tests\Feature;

use App\Actions\Inventory\InventoryBatch\DeleteInventoryBatchAction;
use App\Actions\Product\DeleteProductAction;
use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeleteUnusedInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_with_only_initial_receipt_movement_can_be_deleted_with_stock_remaining(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, quantity: 12);
        $movement = $this->createMovement($batch, 'inventory_receipt');

        app(DeleteInventoryBatchAction::class)->execute($batch);

        $this->assertSoftDeleted('inventory_batches', ['id' => $batch->id]);
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
        $this->assertTrue(
            InventoryMovement::query()->findOrFail($movement->id)->inventoryBatch()->withTrashed()->exists()
        );
    }

    public function test_batch_with_operational_movement_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, quantity: 8);
        $this->createMovement($batch, 'order_fulfillment');

        try {
            app(DeleteInventoryBatchAction::class)->execute($batch);
            $this->fail('A batch with operational movement history must not be deleted.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseHas('inventory_batches', [
                'id' => $batch->id,
                'deleted_at' => null,
            ]);
        }
    }

    public function test_product_deletion_soft_deletes_its_unused_batches_and_keeps_receipt_history(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, quantity: 5);
        $movement = $this->createMovement($batch, 'inventory_initial_balance');

        app(DeleteProductAction::class)->execute($product);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertSoftDeleted('inventory_batches', ['id' => $batch->id]);
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
    }

    public function test_product_with_a_soft_deleted_doctor_visit_sample_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $now = now();

        $doctorId = DB::table('doctors')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'DOC-' . Str::upper(Str::random(8)),
            'first_name' => 'Test',
            'last_name' => 'Doctor',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $visitId = DB::table('visits')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'doctor_id' => $doctorId,
            'employee_id' => $employeeId,
            'visit_date' => $now,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sampleId = DB::table('samples')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'visit_id' => $visitId,
            'product_id' => $product->id,
            'quantity' => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $now,
        ]);

        try {
            app(DeleteProductAction::class)->execute($product);
            $this->fail('A product referenced by a doctor-visit sample must not be deleted.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'deleted_at' => null,
            ]);
            $this->assertDatabaseHas('samples', [
                'id' => $sampleId,
                'deleted_at' => $now,
            ]);
        }
    }

    public function test_product_with_a_batch_that_has_operational_history_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, quantity: 5);
        $this->createMovement($batch, 'scientific_visitor_transfer');

        try {
            app(DeleteProductAction::class)->execute($product);
            $this->fail('A product with operational batch history must not be deleted.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'deleted_at' => null,
            ]);
            $this->assertDatabaseHas('inventory_batches', [
                'id' => $batch->id,
                'deleted_at' => null,
            ]);
        }
    }

    private function createProduct(): Product
    {
        $now = now();

        $brandId = DB::table('brands')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'BR-' . Str::upper(Str::random(8)),
            'title' => 'Deletion test brand',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categoryId = DB::table('product_categories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CAT-' . Str::upper(Str::random(8)),
            'title' => 'Deletion test category',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'PRO-' . Str::upper(Str::random(8)),
            'brand_id' => $brandId,
            'product_category_id' => $categoryId,
            'title' => 'Deletion test product',
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Product::query()->findOrFail($productId);
    }

    private function createBatch(Product $product, int $quantity): InventoryBatch
    {
        $now = now();

        $batchId = DB::table('inventory_batches')->insertGetId([
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

        return InventoryBatch::query()->findOrFail($batchId);
    }

    private function createMovement(InventoryBatch $batch, string $reason): InventoryMovement
    {
        $now = now();

        $movementId = DB::table('inventory_movements')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'inventory_batch_id' => $batch->id,
            'type' => 'in',
            'quantity' => 1,
            'reason' => $reason,
            'moved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return InventoryMovement::query()->findOrFail($movementId);
    }
}
