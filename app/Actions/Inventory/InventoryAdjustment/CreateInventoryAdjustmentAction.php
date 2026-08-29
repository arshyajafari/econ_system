<?php

    namespace App\Actions\Inventory\InventoryAdjustment;

    use App\Enums\InventoryAdjustmentType;
    use App\Enums\InventoryMovementType;
    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryAdjustment;
    use App\Models\InventoryBatch;
    use App\Models\InventoryMovement;
    use Illuminate\Support\Facades\DB;

    class CreateInventoryAdjustmentAction {
        public function execute(array $data): InventoryAdjustment {
            return DB::transaction(function () use ($data) {
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($data['inventory_batch_id']);

                $type = $data['type'];
                $quantity = (int)$data['quantity'];

                if ($quantity <= 0) {
                    throw new BusinessRuleException('مقدار اصلاح موجودی باید بیشتر از صفر باشد.');
                }

                if ($type === InventoryAdjustmentType::DECREASE) {
                    $availableQuantity = $batch->available_quantity;

                    if ($quantity > $availableQuantity) {
                        throw new BusinessRuleException('موجودی قابل دسترس برای این اصلاح کافی نیست.');
                    }

                    $batch->quantity -= $quantity;
                }

                if ($type === InventoryAdjustmentType::INCREASE) {
                    $batch->quantity += $quantity;
                }

                $batch->save();

                $adjustment = new InventoryAdjustment();
                $adjustment->fill($data);
                $adjustment->save();

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => $type === InventoryAdjustmentType::INCREASE ? InventoryMovementType::IN : InventoryMovementType::OUT,
                    'quantity' => $quantity,
                    'reason' => 'inventory_adjustment',
                    'description' => $data['description'] ?? null,
                    'moved_at' => now(),
                ]);

                return $adjustment->fresh(InventoryAdjustment::DEFAULT_RELATIONS);
            });
        }
    }
