<?php

namespace Tests\Feature;

use App\Actions\Visit\CancelVisitAction;
use App\Actions\Visit\DeleteVisitAction;
use App\Actions\Visit\ReleaseVisitSamplesInventoryAction;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitInventoryReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_a_visit_returns_sample_quantity_to_visitor_once(): void
    {
        $ids = $this->createScientificVisitorVisit();

        $visit = Visit::query()->findOrFail($ids['visit_id']);
        app(CancelVisitAction::class)->execute($visit, app(ReleaseVisitSamplesInventoryAction::class));

        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $ids['inventory_id'],
            'received_quantity' => 5,
            'used_quantity' => 0,
        ]);
        $this->assertDatabaseHas('scientific_visitor_inventory_transfers', [
            'id' => $ids['transfer_id'],
            'quantity' => 5,
            'used_quantity' => 0,
            'returned_quantity' => 0,
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $ids['visit_id'],
            'status' => 'cancelled',
        ]);
        $this->assertNotNull(DB::table('visits')->where('id', $ids['visit_id'])->value('inventory_released_at'));

        // Deleting a visit after cancellation must not release the same sample twice.
        app(DeleteVisitAction::class)->execute(
            Visit::query()->findOrFail($ids['visit_id']),
            app(ReleaseVisitSamplesInventoryAction::class)
        );

        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $ids['inventory_id'],
            'received_quantity' => 5,
            'used_quantity' => 0,
        ]);
        $this->assertDatabaseHas('inventory_batches', [
            'id' => $ids['batch_id'],
            'quantity' => 10,
        ]);
    }

    public function test_deleting_a_visit_releases_its_sample_without_changing_main_stock(): void
    {
        $ids = $this->createScientificVisitorVisit();

        app(DeleteVisitAction::class)->execute(
            Visit::query()->findOrFail($ids['visit_id']),
            app(ReleaseVisitSamplesInventoryAction::class)
        );

        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $ids['inventory_id'],
            'received_quantity' => 5,
            'used_quantity' => 0,
        ]);
        $this->assertDatabaseHas('scientific_visitor_inventory_transfers', [
            'id' => $ids['transfer_id'],
            'used_quantity' => 0,
            'returned_quantity' => 0,
        ]);
        $this->assertSoftDeleted('visits', ['id' => $ids['visit_id']]);
        $this->assertSoftDeleted('samples', ['id' => $ids['sample_id']]);
        $this->assertDatabaseHas('inventory_batches', [
            'id' => $ids['batch_id'],
            'quantity' => 10,
        ]);
    }


    public function test_transfer_usage_is_rebuilt_when_legacy_allocation_is_stale(): void
    {
        $ids = $this->createScientificVisitorVisit();

        // Reproduce old rows where aggregate usage is correct but delivery rows
        // still report zero consumption and therefore show the original quantity.
        DB::table('scientific_visitor_inventory_transfers')
            ->where('id', $ids['transfer_id'])
            ->update(['used_quantity' => 0]);

        DB::transaction(function () use ($ids) {
            $inventory = \\App\\Models\\ScientificVisitorInventory::query()
                ->lockForUpdate()
                ->findOrFail($ids['inventory_id']);

            app(AdjustScientificVisitorInventoryUsageAction::class)->execute($inventory, 1);
        });

        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $ids['inventory_id'],
            'received_quantity' => 5,
            'used_quantity' => 3,
        ]);
        $this->assertDatabaseHas('scientific_visitor_inventory_transfers', [
            'id' => $ids['transfer_id'],
            'quantity' => 5,
            'used_quantity' => 3,
            'returned_quantity' => 0,
        ]);
    }

    public function test_reconciliation_repairs_existing_usage_from_active_visit_samples(): void
    {
        $ids = $this->createScientificVisitorVisit();

        DB::table('scientific_visitor_inventories')
            ->where('id', $ids['inventory_id'])
            ->update(['used_quantity' => 0]);
        DB::table('scientific_visitor_inventory_transfers')
            ->where('id', $ids['transfer_id'])
            ->update(['used_quantity' => 0]);

        $summary = app(ReconcileScientificVisitorInventoryUsageAction::class)->execute();

        $this->assertSame(1, $summary['updated']);
        $this->assertSame([], $summary['skipped']);
        $this->assertDatabaseHas('scientific_visitor_inventories', [
            'id' => $ids['inventory_id'],
            'received_quantity' => 5,
            'used_quantity' => 2,
        ]);
        $this->assertDatabaseHas('scientific_visitor_inventory_transfers', [
            'id' => $ids['transfer_id'],
            'quantity' => 5,
            'used_quantity' => 2,
            'returned_quantity' => 0,
        ]);
    }

    private function createScientificVisitorVisit(): array
    {
        $now = now();
        $brandId = DB::table('brands')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'BR-' . Str::upper(Str::random(8)),
            'title' => 'Inventory test brand',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $categoryId = DB::table('product_categories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'CAT-' . Str::upper(Str::random(8)),
            'title' => 'Inventory test category',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $productId = DB::table('products')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'PRO-' . Str::upper(Str::random(8)),
            'brand_id' => $brandId,
            'product_category_id' => $categoryId,
            'title' => 'Inventory test product',
            'status' => 'active',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $employeeId = DB::table('employees')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'EMP-' . Str::upper(Str::random(8)),
            'first_name' => 'Test',
            'last_name' => 'Visitor',
            'national_code' => Str::upper(Str::random(10)),
            'phone_number' => '09' . random_int(100000000, 999999999),
            'hire_date' => $now->toDateString(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $user = User::query()->create([
            'employee_id' => $employeeId,
            'login' => 'visitor-' . Str::lower(Str::random(10)),
            'password' => 'password',
            'status' => 'active',
        ]);
        Role::findOrCreate('scientific visitor', 'web');
        $user->assignRole('scientific visitor');

        $doctorId = DB::table('doctors')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'code' => 'DOC-' . Str::upper(Str::random(8)),
            'first_name' => 'Test',
            'last_name' => 'Doctor',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $batchId = DB::table('inventory_batches')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'product_id' => $productId,
            'batch_number' => 'B-' . Str::upper(Str::random(8)),
            'quantity' => 10,
            'reserved_quantity' => 0,
            'purchase_price' => 100,
            'received_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $inventoryId = DB::table('scientific_visitor_inventories')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'employee_id' => $employeeId,
            'product_id' => $productId,
            'received_quantity' => 5,
            'used_quantity' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $transferId = DB::table('scientific_visitor_inventory_transfers')->insertGetId([
            'scientific_visitor_inventory_id' => $inventoryId,
            'inventory_batch_id' => $batchId,
            'quantity' => 5,
            'used_quantity' => 2,
            'returned_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $visitId = DB::table('visits')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'doctor_id' => $doctorId,
            'employee_id' => $employeeId,
            'visit_date' => $now,
            'status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $sampleId = DB::table('samples')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'visit_id' => $visitId,
            'product_id' => $productId,
            'quantity' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return compact('visitId', 'inventoryId', 'transferId', 'batchId', 'sampleId') + [
            'visit_id' => $visitId,
            'inventory_id' => $inventoryId,
            'transfer_id' => $transferId,
            'batch_id' => $batchId,
            'sample_id' => $sampleId,
        ];
    }
}
