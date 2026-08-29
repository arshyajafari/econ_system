<?php

    namespace App\Actions\Sample;

    use App\Enums\VisitStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Sample;
    use Illuminate\Support\Facades\DB;

    class UpdateSampleAction {
        public function execute(Sample $sample, array $data): Sample {
            return DB::transaction(function () use ($sample, $data) {
                $sample = Sample::query()->lockForUpdate()->with('visit')->findOrFail($sample->id);

                if (!$sample->visit) {
                    throw new BusinessRuleException('بازدید مربوط به نمونه پیدا نشد.');
                }

                if ($sample->visit->status !== VisitStatus::COMPLETED) {
                    throw new BusinessRuleException('فقط نمونه مربوط به بازدید تکمیل‌شده قابل ویرایش است.');
                }

                $quantity = (int)$data['quantity'];

                if ($quantity <= 0) {
                    throw new BusinessRuleException('تعداد نمونه باید بیشتر از صفر باشد.');
                }

                $sample->update([
                    'quantity' => $quantity,
                    'description' => $data['description'] ?? null,
                ]);

                return $sample->fresh(Sample::DEFAULT_RELATIONS);
            });
        }
    }
