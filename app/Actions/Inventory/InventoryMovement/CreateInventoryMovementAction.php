<?php

    namespace App\Actions\Inventory\InventoryMovement;

    use App\Enums\InventoryMovementType;
    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use App\Models\InventoryMovement;
    use Illuminate\Support\Facades\DB;

    class CreateInventoryMovementAction {
        public function execute(array $data): InventoryMovement {
            return DB::transaction(function () use ($data) {
                $quantity = (int)$data['quantity'];

                if ($quantity <= 0) {
                    throw new BusinessRuleException('مقدار حرکت موجودی باید بیشتر از صفر باشد.');
                }

                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($data['inventory_batch_id']);

                $type = $data['type'];

                if ($type === InventoryMovementType::OUT) {
                    $availableQuantity = $batch->quantity - $batch->reserved_quantity;

                    if ($quantity > $availableQuantity) {
                        throw new BusinessRuleException('موجودی قابل دسترس برای این حرکت کافی نیست.');
                    }

                    $batch->quantity -= $quantity;
                }

                if ($type === InventoryMovementType::IN) {
                    $batch->quantity += $quantity;
                }

                $batch->save();

                $movement = new InventoryMovement();
                $movement->fill([
                    ...$data,
                    'quantity' => $quantity,
                ]);
                $movement->moved_at ??= now();
                $movement->save();

                return $movement->fresh(InventoryMovement::DEFAULT_RELATIONS);
            });
        }
    }
