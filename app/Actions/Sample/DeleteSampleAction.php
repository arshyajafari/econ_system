<?php

    namespace App\Actions\Sample;

    use App\Models\Sample;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class DeleteSampleAction {
        public function execute(Sample $sample): void {
            DB::transaction(function () use ($sample) {
                $sample = Sample::query()->lockForUpdate()->with('visit')->findOrFail($sample->id);

                if (!$sample->visit) {
                    throw new RuntimeException('بازدید مربوط به نمونه پیدا نشد.');
                }

                if ($sample->visit->status !== \App\Enums\VisitStatus::COMPLETED) {
                    throw new RuntimeException('فقط نمونه مربوط به بازدید تکمیل‌شده قابل حذف است.');
                }

                $sample->delete();
            });
        }
    }
