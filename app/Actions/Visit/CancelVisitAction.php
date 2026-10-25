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

            // Reconcile inventory before changing status. A failure rolls back
            // both the inventory changes and the status change.
            $releaseInventory->execute($visit);

            $visit->status = VisitStatus::CANCELLED;
            $visit->save();

            return $visit->fresh(Visit::DEFAULT_RELATIONS);
        });
    }
}
