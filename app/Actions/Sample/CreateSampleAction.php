<?php

    namespace App\Actions\Sample;

    use App\Enums\VisitStatus;
    use App\Models\Product;
    use App\Models\Sample;
    use App\Models\User;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CreateSampleAction {
        public function execute(array $data, User $user): Sample {
            return DB::transaction(function () use ($data, $user) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new RuntimeException('کاربر فعلی به کارمند متصل نیست.');
                }

                $visit = Visit::query()->lockForUpdate()->where('public_id', $data['visit_id'])->firstOrFail();

                if ((int)$visit->employee_id !== (int)$employee->id) {
                    throw new RuntimeException('این بازدید متعلق به کارمند فعلی نیست.');
                }

                if ($visit->status === VisitStatus::CANCELLED) {
                    throw new RuntimeException('برای بازدید لغوشده نمی‌توان نمونه ثبت کرد.');
                }

                if ($visit->status !== VisitStatus::COMPLETED) {
                    throw new RuntimeException('فقط برای بازدید تکمیل‌شده می‌توان نمونه ثبت کرد.');
                }

                $product = Product::query()->where('public_id', $data['product_id'])->firstOrFail();

                $sample = Sample::create([
                    'visit_id' => $visit->id,
                    'product_id' => $product->id,
                    'quantity' => (int)$data['quantity'],
                    'description' => $data['description'] ?? null,
                ]);

                return $sample->fresh(Sample::DEFAULT_RELATIONS);
            });
        }
    }
