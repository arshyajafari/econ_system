<?php

    namespace App\Actions\Visit;

    use App\Enums\VisitStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;

    class UpdateVisitAction {
        public function execute(Visit $visit, array $data): Visit {
            return DB::transaction(function () use ($visit, $data) {
                $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);

                if ($visit->status !== VisitStatus::DRAFT) {
                    throw new BusinessRuleException('فقط بازدید در وضعیت draft قابل ویرایش است.');
                }

                $visit->update([
                    'visit_date' => $data['visit_date'],
                    'purpose' => $data['purpose'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                return $visit->fresh(Visit::DEFAULT_RELATIONS);
            });
        }
    }
