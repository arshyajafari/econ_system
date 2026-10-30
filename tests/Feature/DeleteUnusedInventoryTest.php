<?php

namespace Tests\Feature;

use App\Actions\Inventory\InventoryBatch\DeleteInventoryBatchAction;
use App\Actions\Product\DeleteProductAction;
use App\Actions\ScientificVisitorInventory\CreateScientificVisitorInventoryAction;
use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
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

    public function test_product_deletion_hides_product_and_preserves_batch_and_receipt_history(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, quantity: 5);
        $movement = $this->createMovement($batch, 'inventory_initial_balance');

        app(DeleteProductAction::class)->execute($product);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('inventory_batches', ['id' => $batch->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
    }

    public function test_product_with_a_soft_deleted_doctor_visit_sample_can_be_removed_from_active_products(): void
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

        app(DeleteProductAction::class)->execute($product);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('samples', [
            'id' => $sampleId,
            'product_id' => $product->id,
            'deleted_at' => $now,
        ]);
    }

    public function test_product_with_a_batch_that_has_operational_history_can_be_removed_without_deleting_ledger(): void
    {
        $product = $this->createProduct();
        $batch = $this->createBatch($product, quantity: 5);
        $this->createMovement($batch, 'scientific_visitor_transfer');

        app(DeleteProductAction::class)->execute($product);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('inventory_batches', [
            'id' => $batch->id,
            'product_id' => $product->id,
            'deleted_at' => null,
        ]);
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

    public function test_assigning_inventory_restores_an_existing_soft_deleted_empty_ledger(): void
    {
        $product = $this->createProduct();
        $employeeId = $this->createEmployee();
        $inventoryId = $this->createScientificVisitorInventory($employeeId, $product->id, received: 0, used: 0);
        $inventory = \App\Models\ScientificVisitorInventory::query()->findOrFail($inventoryId);
        app(\App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryAction::class)->execute($inventory);

        $user = User::query()->create([
            'employee_id' => $employeeId,
            'login' => 'scientific-' . Str::lower(Str::random(10)),
            'password' => 'password',
            'status' => 'active',
        ]);
        \Spatie\Permission\Models\Role::findOrCreate('scientific visitor', 'web');
        $user->assignRole('scientific visitor');

        $batch = $this->createBatch($product, quantity: 10);
        $result = app(CreateScientificVisitorInventoryAction::class)->execute([
            'employee_id' => \App\Models\Employee::query()->findOrFail($employeeId)->public_id,
            'inventory_batch_id' => $batch->public_id,
            'quantity' => 3,
        ]);

        $this->assertSame($inventoryId, $result->id);
        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $inventoryId,
            'deleted_at' => null,
            'received_quantity' => 3,
            'used_quantity' => 0,
        ]);
        $this->assertSame(
            1,
            DB::table('scientific_visitor_inventories')
                ->where('employee_id', $employeeId)
                ->where('product_id', $product->id)
                ->count(),
        );
        $this->assertDatabaseHas('inventory_batches', [
            'id' => $batch->id,
            'quantity' => 7,
        ]);
    }

    public function test_scientific_visitor_inventory_with_transfer_history_cannot_be_deleted(): void
    {
        $product = $this->createProduct();
        $employeeId = $this->createEmployee();
        $inventoryId = $this->createScientificVisitorInventory($employeeId, $product->id, received: 5, used: 2);
        $inventory = \App\Models\ScientificVisitorInventory::query()->findOrFail($inventoryId);

        try {
            app(\App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryAction::class)->execute($inventory);
            $this->fail('A scientific visitor inventory with movement history must not be deleted.');
        } catch (\App\Exceptions\BusinessRuleException) {
            $this->assertDatabaseHas('scientific_visitor_inventories', [
                'id' => $inventoryId,
                'deleted_at' => null,
                'received_quantity' => 5,
                'used_quantity' => 2,
            ]);
        }
    }

    public function test_deleting_unused_tracked_inventory_returns_all_stock_to_source_batch(): void
    {
        $product = $this->createProduct();
        $employeeId = $this->createEmployee();
        $batch = $this->createBatch($product, quantity: 10);
        $inventoryId = $this->createScientificVisitorInventory($employeeId, $product->id, received: 5, used: 0);

        $transferId = DB::table('scientific_visitor_inventory_transfers')->insertGetId([
            'scientific_visitor_inventory_id' => $inventoryId,
            'inventory_batch_id' => $batch->id,
            'quantity' => 5,
            'used_quantity' => 0,
            'returned_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inventory = \App\Models\ScientificVisitorInventory::query()->findOrFail($inventoryId);
        app(\App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryAction::class)->execute($inventory);

        $this->assertDatabaseHas('inventory_batches', ['id' => $batch->id, 'quantity' => 15]);
        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $inventoryId,
            'received_quantity' => 0,
            'used_quantity' => 0,
        ]);
        $this->assertSoftDeleted('scientific_visitor_inventories', ['id' => $inventoryId]);
        $this->assertDatabaseHas('scientific_visitor_inventory_transfers', [
            'id' => $transferId,
            'quantity' => 5,
            'used_quantity' => 0,
            'returned_quantity' => 5,
        ]);
    }

    public function test_empty_scientific_visitor_inventory_can_be_deleted(): void
    {
        $product = $this->createProduct();
        $employeeId = $this->createEmployee();
        $inventoryId = $this->createScientificVisitorInventory($employeeId, $product->id, received: 0, used: 0);
        $inventory = \App\Models\ScientificVisitorInventory::query()->findOrFail($inventoryId);

        app(\App\Actions\ScientificVisitorInventory\DeleteScientificVisitorInventoryAction::class)->execute($inventory);

        $this->assertSoftDeleted('scientific_visitor_inventories', ['id' => $inventoryId]);
    }

    private function createEmployee(): int
    {
        $now = now();

        return DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Scientific',
            'last_name' => 'Visitor',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createScientificVisitorInventory(int $employeeId, int $productId, int $received, int $used): int
    {
        $now = now();

        return DB::table('scientific_visitor_inventories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'employee_id' => $employeeId,
            'product_id' => $productId,
            'received_quantity' => $received,
            'used_quantity' => $used,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }


    public function test_scientific_visitor_inventory_quantity_cannot_be_changed_without_batch_traceability(): void
    {
        $product = $this->createProduct();
        $employeeId = $this->createEmployee();
        $inventoryId = $this->createScientificVisitorInventory($employeeId, $product->id, received: 5, used: 2);
        $inventory = \App\Models\ScientificVisitorInventory::query()->findOrFail($inventoryId);
        $batch = $this->createBatch($product, quantity: 10);
        $action = app(\App\Actions\ScientificVisitorInventory\UpdateScientificVisitorInventoryAction::class);

        foreach ([4, 6] as $newQuantity) {
            try {
                $action->execute($inventory, [
                    'quantity' => $newQuantity,
                    'inventory_batch_id' => $batch->public_id,
                ]);
                $this->fail('Aggregate quantity edits must not change warehouse stock without source-batch allocations.');
            } catch (\App\Exceptions\BusinessRuleException) {
                $this->assertDatabaseHas('scientific_visitor_inventories', [
                    'id' => $inventoryId,
                    'received_quantity' => 5,
                    'used_quantity' => 2,
                    'deleted_at' => null,
                ]);
                $this->assertDatabaseHas('inventory_batches', [
                    'id' => $batch->id,
                    'quantity' => 10,
                ]);
            }
        }
    }

}
