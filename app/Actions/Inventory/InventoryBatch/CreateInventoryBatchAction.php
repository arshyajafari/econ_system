<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class CreateInventoryBatchAction {
        public function execute(array $data): InventoryBatch {
            return DB::transaction(function () use ($data) {
                $quantity = (int)($data['quantity'] ?? 0);

                if ($quantity < 0) {
                    throw new BusinessRuleException('موجودی اولیه نمی‌تواند منفی باشد.');
                }

                unset($data['reserved_quantity']);

                $batch = new InventoryBatch();

                $batch->fill([
                    ...$data,
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                ]);

                $batch->save();

                return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
            });
        }
    }
