<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class DeleteInventoryBatchAction {
        public function execute(InventoryBatch $batch): void {
            DB::transaction(function () use ($batch) {
                /**
                 * بعداً:
                 *
                 * اگر Movement داشت
                 * اجازه حذف نده.
                 */

                $batch->delete();
            });
        }
    }
