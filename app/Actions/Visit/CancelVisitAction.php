<?php

    namespace App\Actions\Visit;

    use App\Enums\VisitStatus;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CancelVisitAction {
        public function execute(Visit $visit): Visit {
            return DB::transaction(function () use ($visit) {
                $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);

                if (in_array($visit->status, [
                        VisitStatus::COMPLETED,
                        VisitStatus::CANCELLED,
                    ], true)) {
                    throw new RuntimeException('این بازدید قابل لغو نیست.');
                }

                $visit->status = VisitStatus::CANCELLED;
                $visit->save();

                return $visit->fresh(Visit::DEFAULT_RELATIONS);
            });
        }
    }
