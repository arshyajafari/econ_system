<?php

namespace App\Actions\Visit;

use App\Enums\VisitStatus;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class DeleteVisitAction
{
    public function execute(Visit $visit, ReleaseVisitSamplesInventoryAction $releaseInventory): void
    {
        DB::transaction(function () use ($visit, $releaseInventory) {
            $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);

            // A cancelled visit already returned its samples to inventory.
            // Do not release them a second time when that visit is later deleted.
            if ($visit->status !== VisitStatus::CANCELLED) {
                $releaseInventory->execute($visit);
            }

            $visit->delete();
        });
    }
}
