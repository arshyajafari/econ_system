<?php

    namespace App\Actions\Sample;

    use App\Enums\VisitStatus;
    use App\Models\Sample;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class UpdateSampleAction {
        public function execute(Sample $sample, array $data): Sample {
            return DB::transaction(function () use ($sample, $data) {
                $sample = Sample::query()->lockForUpdate()->with('visit')->findOrFail($sample->id);

                if (!$sample->visit) {
                    throw new RuntimeException('بازدید مربوط به نمونه پیدا نشد.');
                }

                if ($sample->visit->status !== VisitStatus::COMPLETED) {
                    throw new RuntimeException('فقط نمونه مربوط به بازدید تکمیل‌شده قابل ویرایش است.');
                }

                $sample->update([
                    'quantity' => (int)$data['quantity'],
                    'description' => $data['description'] ?? null,
                ]);

                return $sample->fresh(Sample::DEFAULT_RELATIONS);
            });
        }
    }
