<?php

    namespace App\Actions\Inventory\InventoryMovement;

    use App\Enums\InventoryMovementType;
    use App\Models\InventoryBatch;
    use App\Models\InventoryMovement;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CreateInventoryMovementAction {
        public function execute(array $data): InventoryMovement {
            return DB::transaction(function () use ($data) {

                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($data['inventory_batch_id']);

                $type = $data['type'];
                $quantity = (int)$data['quantity'];

                if ($type === InventoryMovementType::OUT) {
                    $availableQuantity = $batch->quantity - $batch->reserved_quantity;

                    if ($quantity > $availableQuantity) {
                        throw new RuntimeException('موجودی قابل دسترس برای این حرکت کافی نیست.');
                    }

                    $batch->quantity -= $quantity;
                }

                if ($type === InventoryMovementType::IN) {
                    $batch->quantity += $quantity;
                }

                $batch->save();

                $movement = new InventoryMovement();
                $movement->fill($data);
                $movement->moved_at ??= now();
                $movement->save();

                return $movement->fresh(InventoryMovement::DEFAULT_RELATIONS);
            });
        }
    }
