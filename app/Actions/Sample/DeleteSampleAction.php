<?php

    namespace App\Actions\Sample;

    use App\Enums\VisitStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Sample;
    use Illuminate\Support\Facades\DB;

    class DeleteSampleAction {
        public function execute(Sample $sample): void {
            DB::transaction(function () use ($sample) {
                $sample = Sample::query()->lockForUpdate()->with('visit')->findOrFail($sample->id);

                if (!$sample->visit) {
                    throw new BusinessRuleException('بازدید مربوط به نمونه پیدا نشد.');
                }

                if ($sample->visit->status !== VisitStatus::COMPLETED) {
                    throw new BusinessRuleException('فقط نمونه مربوط به بازدید تکمیل‌شده قابل حذف است.');
                }

                $sample->delete();
            });
        }
    }
