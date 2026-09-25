<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class DeleteScientificVisitorInventoryAction
{
    public function execute(ScientificVisitorInventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            $inventory = ScientificVisitorInventory::query()
                ->lockForUpdate()
                ->findOrFail($inventory->id);

            // The current scientific visitor inventory record aggregates transfers
            // by employee/product and does not retain a source inventory batch.
            // Therefore deletion intentionally soft-deletes only the visitor ledger
            // and does not alter warehouse quantities or historical movements.
            $inventory->delete();
        });
    }
}
