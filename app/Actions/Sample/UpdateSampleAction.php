<?php

namespace App\Actions\Sample;

use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Sample;
use App\Models\ScientificVisitorInventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateSampleAction
{
    public function execute(Sample $sample, array $data, User $user): Sample
    {
        return DB::transaction(function () use ($sample, $data, $user) {
            $sample = Sample::query()->lockForUpdate()->with(['visit', 'product'])->findOrFail($sample->id);

            if (!$sample->visit) {
                throw new BusinessRuleException('بازدید مربوط به نمونه پیدا نشد.');
            }

            if ((int) $sample->visit->employee_id !== (int) $user->employee?->id && $user->hasRole('scientific visitor')) {
                throw new BusinessRuleException('این نمونه متعلق به کارمند فعلی نیست.');
            }

            if ($sample->visit->status !== VisitStatus::COMPLETED) {
                throw new BusinessRuleException('فقط نمونه مربوط به بازدید تکمیل‌شده قابل ویرایش است.');
            }

            $quantity = (int) $data['quantity'];
            if ($quantity <= 0) {
                throw new BusinessRuleException('تعداد نمونه باید بیشتر از صفر باشد.');
            }

            if ($user->hasRole('scientific visitor')) {
                $inventory = ScientificVisitorInventory::query()
                    ->where('employee_id', $user->employee?->id)
                    ->where('product_id', $sample->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $availableAfterRestoring = $inventory->available_quantity + $sample->quantity;
                if ($availableAfterRestoring < $quantity) {
                    throw new BusinessRuleException('موجودی این محصول برای مقدار جدید نمونه کافی نیست.');
                }

                $inventory->used_quantity = $inventory->used_quantity - $sample->quantity + $quantity;
                $inventory->save();
            }

            $sample->update([
                'quantity' => $quantity,
                'description' => $data['description'] ?? null,
            ]);

            return $sample->fresh(Sample::DEFAULT_RELATIONS);
        });
    }
}