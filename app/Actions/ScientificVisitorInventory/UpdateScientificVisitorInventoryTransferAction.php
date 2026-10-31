<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class UpdateScientificVisitorInventoryTransferAction
{
    public function execute(ScientificVisitorInventory $inventory, int $transferId, array $data): array
    {
        return DB::transaction(function () use ($inventory, $transferId, $data) {
            $ledger = ScientificVisitorInventory::query()
                ->lockForUpdate()
                ->findOrFail($inventory->id);

            $transfer = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $ledger->id)
                ->where('id', $transferId)
                ->lockForUpdate()
                ->first();

            if (!$transfer) {
                throw new BusinessRuleException('ردیف تحویل پیدا نشد.');
            }

            $quantity = (int) $transfer->quantity;
            $returned = (int) $transfer->returned_quantity;
            $used = (int) $transfer->used_quantity;
            $oldActive = $quantity - $returned;

            if ($quantity < 0 || $returned < 0 || $returned > $quantity || $used < 0 || $used > $oldActive) {
                throw new BusinessRuleException('سوابق این ردیف تحویل ناسازگار است؛ ویرایش برای جلوگیری از خطای انبار متوقف شد.');
            }

            // Reconcile tracked batches while allowing a legitimate legacy remainder.
            // Older inventory rows may contain quantities recorded before batch-level
            // tracking was introduced; those quantities must remain untouched.
            $allTransfers = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $ledger->id)
                ->lockForUpdate()
                ->get();

            foreach ($allTransfers as $row) {
                $rowQuantity = (int) $row->quantity;
                $rowReturned = (int) $row->returned_quantity;
                $rowUsed = (int) $row->used_quantity;
                $rowActive = $rowQuantity - $rowReturned;

                if (
                    $rowQuantity < 0 ||
                    $rowReturned < 0 ||
                    $rowReturned > $rowQuantity ||
                    $rowUsed < 0 ||
                    $rowUsed > $rowActive
                ) {
                    throw new BusinessRuleException('سوابق یکی از بچ‌های تحویلی نامعتبر است؛ هیچ تغییری در انبار اعمال نشد.');
                }

            }

            // The aggregate ledger can legitimately contain historical quantities
            // that were created before per-batch transfer tracking. Do not block a
            // safe edit merely because those legacy totals are not represented by
            // transfer rows. Keep every legacy difference unchanged by applying
            // the same delta to this transfer and to the aggregate ledger.
            $ledgerReceived = (int) $ledger->received_quantity;
            $ledgerUsed = (int) $ledger->used_quantity;

            if ($ledgerReceived < 0 || $ledgerUsed < 0 || $ledgerUsed > $ledgerReceived) {
                throw new BusinessRuleException('موجودی کل و مصرف ویزیتور معتبر نیست؛ برای جلوگیری از ثبت موجودی منفی، ویرایش انجام نشد.');
            }

            $newActiveQuantity = (int) $data['quantity'];
            if ($newActiveQuantity < $used) {
                throw new BusinessRuleException("تعداد تحویل نمی‌تواند کمتر از مصرف این بچ ({$used}) باشد.");
            }

            $delta = $newActiveQuantity - $oldActive;

            if ($delta > 0) {
                // An edit always remains attached to its original source batch.
                // A different batch must be registered as a separate delivery.
                $batch = InventoryBatch::query()
                    ->lockForUpdate()
                    ->find($transfer->inventory_batch_id);

                if (!$batch || (int) $batch->product_id !== (int) $ledger->product_id) {
                    throw new BusinessRuleException('بچ اصلی این تحویل پیدا نشد یا متعلق به محصول ویزیتور نیست؛ برای بچ جدید یک تحویل جداگانه ثبت کنید.');
                }

                if ($batch->is_expired) {
                    throw new BusinessRuleException('افزایش موجودی از بچ منقضی‌شده مجاز نیست.');
                }

                if ($delta > (int) $batch->available_quantity) {
                    throw new BusinessRuleException('موجودی قابل تحویل بچ اصلی کافی نیست؛ برای استفاده از بچ دیگر، تحویل جدید ثبت کنید.');
                }

                $batch->quantity -= $delta;
                $batch->save();

                DB::table('scientific_visitor_inventory_transfers')
                    ->where('id', $transferId)
                    ->update([
                        'quantity' => $quantity + $delta,
                        'updated_at' => now(),
                    ]);

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::OUT,
                    'quantity' => $delta,
                    'reason' => 'scientific_visitor_transfer',
                    'description' => $data['description'] ?? 'افزایش مقدار همان بچ تحویل ویزیتور علمی از طریق ویرایش',
                    'moved_at' => now(),
                ]);

                $ledger->received_quantity += $delta;
                $ledger->last_received_at = now();
            } elseif ($delta < 0) {
                $return = abs($delta);
                $returnable = $oldActive - $used;

                $aggregateAvailable = $ledgerReceived - $ledgerUsed;
                $safeReturnable = min($returnable, $aggregateAvailable);

                if ($return > $safeReturnable) {
                    throw new BusinessRuleException('مقدار کاهش از موجودی قابل استفاده این بچ یا موجودی کل ویزیتور بیشتر است؛ مقدار مصرف‌شده قابل کاهش یا برگشت نیست.');
                }

                $batch = InventoryBatch::query()
                    ->lockForUpdate()
                    ->find($transfer->inventory_batch_id);

                if (!$batch) {
                    throw new BusinessRuleException('بچ اصلی این تحویل پیدا نشد؛ برای جلوگیری از مغایرت، کاهش انجام نشد.');
                }

                $batch->quantity += $return;
                $batch->save();

                DB::table('scientific_visitor_inventory_transfers')
                    ->where('id', $transferId)
                    ->update([
                        'returned_quantity' => $returned + $return,
                        'updated_at' => now(),
                    ]);

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::IN,
                    'quantity' => $return,
                    'reason' => 'scientific_visitor_transfer_return',
                    'description' => $data['description'] ?? 'برگشت اصلاح مقدار تحویل ویزیتور علمی به همان بچ',
                    'moved_at' => now(),
                ]);

                $ledger->received_quantity -= $return;
            }

            if (array_key_exists('description', $data)) {
                $ledger->description = $data['description'];
            }

            $ledger->save();

            $updatedTransfer = DB::table('scientific_visitor_inventory_transfers')
                ->where('id', $transferId)
                ->first();

            return $updatedTransfer ? (array) $updatedTransfer : [];
        });
    }
}
