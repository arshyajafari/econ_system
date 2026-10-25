<?php

namespace App\Actions\Visit;

use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class DeleteVisitAction
{
    public function execute(Visit $visit, ReleaseVisitSamplesInventoryAction $releaseInventory): void
    {
        DB::transaction(function () use ($visit, $releaseInventory) {
            $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);

            // This timestamp makes deletion safe after cancellation and also
            // lets legacy cancelled visits be reconciled when they are deleted.
            if ($visit->inventory_released_at === null) {
                $releaseInventory->execute($visit);
                $visit->inventory_released_at = now();
                $visit->save();
            }

            // Keep historical rows for audit, but hide samples belonging to a deleted visit.
            $visit->samples()->get()->each(fn ($sample) => $sample->delete());
            $visit->delete();
        });
    }
}
