<?php

namespace App\Actions\Visit;

use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class CancelVisitAction
{
    public function execute(Visit $visit, ReleaseVisitSamplesInventoryAction $releaseInventory): Visit
    {
        return DB::transaction(function () use ($visit, $releaseInventory) {
            $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);

            if (in_array($visit->status, [
                VisitStatus::COMPLETED,
                VisitStatus::CANCELLED,
            ], true)) {
                throw new BusinessRuleException('این بازدید قابل لغو نیست.');
            }

            // Inventory is released exactly once. If reconciliation fails, the
            // transaction rolls back and the visit remains in its old status.
            if ($visit->inventory_released_at === null) {
                $releaseInventory->execute($visit);
                $visit->inventory_released_at = now();
            }

            $visit->status = VisitStatus::CANCELLED;
            $visit->save();

            return $visit->fresh(Visit::DEFAULT_RELATIONS);
        });
    }
}
