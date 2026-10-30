<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ScientificVisitorInventory;
use Carbon\Carbon;
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

            if ($quantity > (int) $batch->available_quantity) {
                throw new BusinessRuleException('موجودی قابل تحویل این بچ کافی نیست.');
            }

            $deliveredAt = Carbon::parse($data['delivered_at'] ?? now())->toDateString();

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
                $inventory->restore();
            }

            $inventory->received_quantity += $quantity;
            $inventory->last_received_at = now();
            $inventory->description = $data['description'] ?? $inventory->description;
            $inventory->save();

            $batch->quantity -= $quantity;
            $batch->save();

            // Same visitor + product + source batch + delivery day is one
            // logical delivery row. A different day remains a separate row.
            $transfer = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $inventory->id)
                ->where('inventory_batch_id', $batch->id)
                ->whereDate('delivered_at', $deliveredAt)
                ->lockForUpdate()
                ->first();

            if ($transfer) {
                DB::table('scientific_visitor_inventory_transfers')
                    ->where('id', $transfer->id)
                    ->increment('quantity', $quantity, ['updated_at' => now()]);
            } else {
                DB::table('scientific_visitor_inventory_transfers')->insert([
                    'scientific_visitor_inventory_id' => $inventory->id,
                    'inventory_batch_id' => $batch->id,
                    'quantity' => $quantity,
                    'used_quantity' => 0,
                    'returned_quantity' => 0,
                    'delivered_at' => $deliveredAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
