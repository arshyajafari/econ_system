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

                $type = $data['type'] instanceof InventoryAdjustmentType ? $data['type'] : InventoryAdjustmentType::tryFrom($data['type']);

                if (!$type) {
                    throw new BusinessRuleException('نوع اصلاح موجودی معتبر نیست.');
                }

                $quantity = (int)$data['quantity'];

                if ($quantity <= 0) {
                    throw new BusinessRuleException('مقدار اصلاح موجودی باید بیشتر از صفر باشد.');
                }

                if ($type === InventoryAdjustmentType::DECREASE) {
                    /*
                     * Reserved inventory belongs to confirmed orders
                     * and must not be removed by a manual adjustment.
                     */
                    if ($quantity > (int)$batch->available_quantity) {
                        throw new BusinessRuleException('موجودی قابل دسترس برای این اصلاح کافی نیست.');
                    }

                    $batch->quantity -= $quantity;
                }

                if ($type === InventoryAdjustmentType::INCREASE) {
                    $batch->quantity += $quantity;
                }

                /*
                 * Defensive inventory invariants.
                 */
                if ($batch->quantity < 0) {
                    throw new BusinessRuleException('موجودی انبار نمی‌تواند منفی شود.');
                }

                if ($batch->reserved_quantity < 0) {
                    throw new BusinessRuleException('موجودی رزروشده نمی‌تواند منفی شود.');
                }

                if ($batch->reserved_quantity > $batch->quantity) {
                    throw new BusinessRuleException('موجودی رزروشده نمی‌تواند بیشتر از موجودی واقعی باشد.');
                }

                $batch->save();

                $adjustment = InventoryAdjustment::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'reason' => $data['reason'],
                    'description' => $data['description'] ?? null,
                ]);

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
