<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class CreateScientificVisitorInventoryAction
{
    public function execute(array $data): ScientificVisitorInventory
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::query()
                ->where('public_id', $data['employee_id'])
                ->whereHas('user', fn ($q) => $q->role('scientific visitor'))
                ->firstOrFail();

            $batch = InventoryBatch::query()
                ->where('public_id', $data['inventory_batch_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($batch->is_expired) {
                throw new BusinessRuleException('تحویل محصول منقضی‌شده به ویزیتور علمی مجاز نیست.');
            }

            $quantity = (int) $data['quantity'];

            if ($quantity <= 0) {
                throw new BusinessRuleException('تعداد تحویلی باید بیشتر از صفر باشد.');
            }

            $available = (int) $batch->available_quantity;
            if ($quantity > $available) {
                throw new BusinessRuleException('موجودی قابل تحویل این بچ کافی نیست.');
            }

            // The employee/product pair has a unique constraint even for soft-deleted
            // rows. Reuse and restore an existing ledger instead of attempting an
            // insert that would fail after an empty ledger was deleted.
            $inventory = ScientificVisitorInventory::withTrashed()
                ->where('employee_id', $employee->id)
                ->where('product_id', $batch->product_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $inventory = new ScientificVisitorInventory([
                    'employee_id' => $employee->id,
                    'product_id' => $batch->product_id,
                    'received_quantity' => 0,
                    'used_quantity' => 0,
                ]);
            } elseif ($inventory->trashed()) {
                // Older deployments may contain deleted ledgers with history.
                // Restore that history; never create a competing aggregate row.
                $inventory->restore();
            }

            $inventory->received_quantity += $quantity;
            $inventory->last_received_at = now();
            $inventory->description = $data['description'] ?? $inventory->description;
            $inventory->save();

            $batch->quantity -= $quantity;
            $batch->save();

            InventoryMovement::create([
                'inventory_batch_id' => $batch->id,
                'type' => InventoryMovementType::OUT,
                'quantity' => $quantity,
                'reason' => 'scientific_visitor_transfer',
                'description' => $data['description'] ?? "تحویل به ویزیتور علمی {$employee->fullName}",
                'moved_at' => now(),
            ]);

            return $inventory->fresh(ScientificVisitorInventory::DEFAULT_RELATIONS);
        });
    }
}