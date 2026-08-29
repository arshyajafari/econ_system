<?php

    namespace App\Actions\Visit;

    use App\Enums\VisitStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;

    class CompleteVisitAction {
        public function execute(Visit $visit): Visit {
            return DB::transaction(function () use ($visit) {
                $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);

                if ($visit->status !== VisitStatus::DRAFT) {
                    throw new BusinessRuleException('فقط بازدید در وضعیت draft قابل تکمیل است.');
                }

                $visit->status = VisitStatus::COMPLETED;
                $visit->save();

                return $visit->fresh(Visit::DEFAULT_RELATIONS);
            });
        }
    }
