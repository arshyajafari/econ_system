<?php

    namespace App\Actions\Sample;

    use App\Enums\VisitStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Product;
    use App\Models\Sample;
    use App\Models\User;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;

    class CreateSampleAction {
        public function execute(array $data, User $user): Sample {
            return DB::transaction(function () use ($data, $user) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
                }

                if (!empty($data['client_operation_id'])) {
                    $existingSample = Sample::query()->where('client_operation_id', $data['client_operation_id'])
                        ->first();

                    if ($existingSample) {
                        return $existingSample->fresh(Sample::DEFAULT_RELATIONS);
                    }
                }

                $visit = Visit::query()->lockForUpdate()->where('public_id', $data['visit_id'])->firstOrFail();

                if ((int)$visit->employee_id !== (int)$employee->id) {
                    throw new BusinessRuleException('این بازدید متعلق به کارمند فعلی نیست.');
                }

                if ($visit->status === VisitStatus::CANCELLED) {
                    throw new BusinessRuleException('برای بازدید لغوشده نمی‌توان نمونه ثبت کرد.');
                }

                if ($visit->status !== VisitStatus::COMPLETED) {
                    throw new BusinessRuleException('فقط برای بازدید تکمیل‌شده می‌توان نمونه ثبت کرد.');
                }

                $product = Product::query()->where('public_id', $data['product_id'])->firstOrFail();

                $sample = Sample::create([
                    'client_operation_id' => $data['client_operation_id'] ?? null,
                    'visit_id' => $visit->id,
                    'product_id' => $product->id,
                    'quantity' => (int)$data['quantity'],
                    'description' => $data['description'] ?? null,
                ]);

                return $sample->fresh(Sample::DEFAULT_RELATIONS);
            });
        }
    }
