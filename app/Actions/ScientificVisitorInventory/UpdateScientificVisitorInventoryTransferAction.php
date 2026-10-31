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
            $ledger = ScientificVisitorInventory::query()->lockForUpdate()->findOrFail($inventory->id);
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

            // Reconcile the entire delivery ledger before changing any stock.
            $allTransfers = DB::table('scientific_visitor_inventory_transfers')
                ->where('scientific_visitor_inventory_id', $ledger->id)
                ->lockForUpdate()
                ->get();
            $trackedReceived = 0;
            $trackedUsed = 0;
            foreach ($allTransfers as $row) {
                $rowQuantity = (int) $row->quantity;
                $rowReturned = (int) $row->returned_quantity;
                $rowUsed = (int) $row->used_quantity;
                $rowActive = $rowQuantity - $rowReturned;
                if ($rowQuantity < 0 || $rowReturned < 0 || $rowReturned > $rowQuantity || $rowUsed < 0 || $rowUsed > $rowActive) {
                    throw new BusinessRuleException('سوابق یکی از بچ‌های تحویلی نامعتبر است؛ هیچ تغییری در انبار اعمال نشد.');
                }
                $trackedReceived += $rowActive;
                $trackedUsed += $rowUsed;
            }

            if ($trackedReceived !== (int) $ledger->received_quantity || $trackedUsed !== (int) $ledger->used_quantity) {
                throw new BusinessRuleException('سوابق تحویل با موجودی و مصرف ثبت‌شده تطبیق ندارد؛ ابتدا باید مغایرت بررسی شود.');
            }

            $newQuantity = (int) $data['quantity'];
            if ($newQuantity < $used) {
                throw new BusinessRuleException("تعداد تحویل نمی‌تواند کمتر از مصرف این ردیف ({$used}) باشد.");
            }

            $delta = $newQuantity - $oldActive;
            if ($delta > 0) {
                $batchPublicId = $data['inventory_batch_id'] ?? null;
                if (!$batchPublicId) {
                    throw new BusinessRuleException('برای افزایش تعداد، بچ انبار اصلی را انتخاب کنید.');
                }

                $batch = InventoryBatch::query()
                    ->where('public_id', $batchPublicId)
                    ->lockForUpdate()
                    ->first();

                if (!$batch || (int) $batch->product_id !== (int) $ledger->product_id) {
                    throw new BusinessRuleException('بچ انتخاب‌شده متعلق به همین محصول نیست.');
                }
                if ($batch->is_expired) {
                    throw new BusinessRuleException('افزایش موجودی از بچ منقضی‌شده مجاز نیست.');
                }
                if ($delta > (int) $batch->available_quantity) {
                    throw new BusinessRuleException('موجودی قابل تحویل بچ انتخاب‌شده کافی نیست.');
                }

                // Preserve source-batch provenance: an increase is a new delivery row,
                // never a mutation of the original row's batch or delivery date.
                DB::table('scientific_visitor_inventory_transfers')->insert([
                    'scientific_visitor_inventory_id' => $ledger->id,
                    'inventory_batch_id' => $batch->id,
                    'quantity' => $delta,
                    'used_quantity' => 0,
                    'returned_quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $batch->quantity -= $delta;
                $batch->save();

                InventoryMovement::create([
                    'inventory_batch_id' => $batch->id,
                    'type' => InventoryMovementType::OUT,
                    'quantity' => $delta,
                    'reason' => 'scientific_visitor_transfer',
                    'description' => $data['description'] ?? 'افزایش موجودی ویزیتور علمی از طریق ویرایش',
                    'moved_at' => now(),
                ]);

                $ledger->received_quantity += $delta;
                $ledger->last_received_at = now();
            } elseif ($delta < 0) {
                $return = abs($delta);
                if ($return > ($oldActive - $used)) {
                    throw new BusinessRuleException('مقدار کاهش از موجودی استفاده‌نشده این ردیف بیشتر است.');
                }

                $batch = InventoryBatch::query()->lockForUpdate()->find($transfer->inventory_batch_id);
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
                    'description' => $data['description'] ?? 'برگشت اصلاح مقدار تحویل ویزیتور علمی',
                    'moved_at' => now(),
                ]);

                $ledger->received_quantity -= $return;
            }

            if (array_key_exists('description', $data)) {
                $ledger->description = $data['description'];
            }
            $ledger->save();

            return DB::table('scientific_visitor_inventory_transfers')->where('id', $transferId)->first();
        });
    }
}
