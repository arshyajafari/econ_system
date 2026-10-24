<?php

namespace App\Actions\Inventory\InventoryBatch;

use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateInventoryBatchAction
{
    public function execute(InventoryBatch $batch, array $data): InventoryBatch
    {
        return DB::transaction(function () use ($batch, $data) {
            $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($batch->id);

            $batchNumber = array_key_exists('batch_number', $data)
                ? $data['batch_number']
                : $batch->batch_number;
            $expireDate = array_key_exists('expire_date', $data)
                ? ($data['expire_date'] === null ? null : Carbon::parse($data['expire_date'])->toDateString())
                : $batch->expire_date?->toDateString();

            $identityChanged = $batchNumber !== $batch->batch_number
                || $expireDate !== $batch->expire_date?->toDateString();

            if ($identityChanged && $batch->hasOperationalHistory()) {
                throw new BusinessRuleException(
                    'شماره بچ و تاریخ انقضای بچ دارای سابقه عملیاتی قابل تغییر نیست؛ برای اصلاح آن باید فرایند اصلاح‌شده و قابل‌ردیابی انجام شود.'
                );
            }

            // Receipt logic merges batches by product + batch number + expiry.
            // Prevent metadata edits from creating duplicate identities that
            // later receipts would merge into an arbitrary matching batch.
            if ($batchNumber !== null) {
                $duplicateQuery = InventoryBatch::query()
                    ->where('product_id', $batch->product_id)
                    ->where('batch_number', $batchNumber)
                    ->where('id', '<>', $batch->id);

                if ($expireDate === null) {
                    $duplicateQuery->whereNull('expire_date');
                } else {
                    $duplicateQuery->whereDate('expire_date', $expireDate);
                }

                if ($duplicateQuery->exists()) {
                    throw new BusinessRuleException(
                        'بچی با همین محصول، شماره بچ و تاریخ انقضا از قبل وجود دارد.'
                    );
                }
            }

            // Quantity changes must go through InventoryAdjustment so the
            // adjustment record and stock movement stay consistent.
            $batch->fill([
                'batch_number' => $batchNumber,
                'expire_date' => $expireDate,
                'description' => array_key_exists('description', $data)
                    ? $data['description']
                    : $batch->description,
            ]);

            $batch->save();

            return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
        });
    }
}
