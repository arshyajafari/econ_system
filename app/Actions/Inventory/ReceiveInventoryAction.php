<?php

    namespace App\Actions\Inventory;

    use App\Enums\InventoryMovementType;
    use App\Models\InventoryBatch;
    use App\Models\InventoryMovement;
    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    class ReceiveInventoryAction {
        public function execute(array $data): InventoryBatch {
            return DB::transaction(function () use ($data) {
                $product = Product::query()->where('public_id', $data['product_id'])->lockForUpdate()->firstOrFail();

                $batchNumber = $data['batch_number'] ?? null;
                $expireDate = $data['expire_date'] ?? null;

                $batch = null;

                if ($batchNumber !== null) {
                    $batch = InventoryBatch::query()->where('product_id', $product->id)
                        ->where('batch_number', $batchNumber)->where(function ($query) use ($expireDate) {
                            if ($expireDate === null) {
                                $query->whereNull('expire_date');
                            } else {
                                $query->whereDate('expire_date', $expireDate);
                            }
                        })->lockForUpdate()->first();
                }

                if ($batch) {
                    $batch->increment('quantity', $data['quantity']);
                } else {
                    $batch = InventoryBatch::create([
                        'product_id' => $product->id,
                        'batch_number' => $batchNumber,
                        'expire_date' => $expireDate,
                        'quantity' => $data['quantity'],
                        'reserved_quantity' => 0,
                        'received_at' => $data['received_at'] ?? now(),
                        'description' => $data['description'] ?? null,
                    ]);
                }

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::IN,
                    'quantity' => $data['quantity'],
                    'reason' => 'inventory_receipt',
                    'description' => $data['description'] ?? null,
                    'moved_at' => $data['received_at'] ?? now(),
                ]);

                return $batch->fresh([
                    'product',
                    'movements',
                ]);
            });
        }
    }
