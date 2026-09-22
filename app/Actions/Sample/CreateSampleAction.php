<?php

namespace App\Actions\Sample;

use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\Sample;
use App\Models\ScientificVisitorInventory;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class CreateSampleAction
{
    public function execute(array $data, User $user): Sample
    {
        return DB::transaction(function () use ($data, $user) {
            $employee = $user->employee;

            if (!$employee) {
                throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
            }

            $visit = Visit::query()
                ->lockForUpdate()
                ->where('public_id', $data['visit_id'])
                ->firstOrFail();

            if ((int) $visit->employee_id !== (int) $employee->id) {
                throw new BusinessRuleException('این بازدید متعلق به کارمند فعلی نیست.');
            }

            if ($visit->status === VisitStatus::CANCELLED) {
                throw new BusinessRuleException('برای بازدید لغوشده نمی‌توان نمونه ثبت کرد.');
            }

            if (!empty($data['client_operation_id'])) {
                $existingSample = Sample::query()
                    ->with(Sample::DEFAULT_RELATIONS)
                    ->where('client_operation_id', $data['client_operation_id'])
                    ->first();

                if ($existingSample) {
                    if ((int) $existingSample->visit?->employee_id !== (int) $employee->id) {
                        throw new BusinessRuleException('این عملیات قبلاً برای کارمند دیگری ثبت شده است.');
                    }

                    return $existingSample;
                }
            }

            $product = Product::query()->where('public_id', $data['product_id'])->firstOrFail();
            $quantity = (int) $data['quantity'];

            if ($quantity <= 0) {
                throw new BusinessRuleException('تعداد نمونه باید بیشتر از صفر باشد.');
            }

            if ($user->hasRole('scientific visitor')) {
                $inventory = ScientificVisitorInventory::query()
                    ->where('employee_id', $employee->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->available_quantity < $quantity) {
                    throw new BusinessRuleException('موجودی این محصول برای ثبت نمونه کافی نیست.');
                }

                $inventory->used_quantity += $quantity;
                $inventory->save();
            }

            $sample = Sample::create([
                'client_operation_id' => $data['client_operation_id'] ?? null,
                'visit_id' => $visit->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'description' => $data['description'] ?? null,
            ]);

            return $sample->fresh(Sample::DEFAULT_RELATIONS);
        });
    }
}