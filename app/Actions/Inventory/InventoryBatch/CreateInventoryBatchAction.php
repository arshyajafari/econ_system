<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Enums\InventoryMovementType;
    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use App\Models\InventoryMovement;
    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    class CreateInventoryBatchAction {
        public function execute(array $data): InventoryBatch {
            return DB::transaction(function () use ($data) {
                $quantity = (int)($data['quantity'] ?? 0);

                if ($quantity <= 0) {
                    throw new BusinessRuleException('موجودی اولیه باید بیشتر از صفر باشد.');
                }

                $product = Product::query()->where('public_id', $data['product_id'])->lockForUpdate()->firstOrFail();

                $batch = new InventoryBatch();

                $batch->fill([
                    'product_id' => $product->id,
                    'batch_number' => $data['batch_number'] ?? null,
                    'expire_date' => $data['expire_date'] ?? null,
                    'received_at' => $data['received_at'] ?? now(),
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'description' => $data['description'] ?? null,
                ]);

                $batch->save();

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::IN,
                    'quantity' => $quantity,
                    'reason' => 'inventory_initial_balance',
                    'description' => $data['description'] ?? 'موجودی اولیه بچ',
                    'moved_at' => $data['received_at'] ?? now(),
                ]);

                return $batch->fresh([
                    'product',
                    'movements',
                ]);
            });
        }
    }
