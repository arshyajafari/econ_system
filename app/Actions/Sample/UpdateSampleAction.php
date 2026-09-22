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
            $sample = Sample::query()
                ->lockForUpdate()
                ->with(['visit.employee.user', 'product'])
                ->findOrFail($sample->id);

            if (!$sample->visit) {
                throw new BusinessRuleException('بازدید مربوط به نمونه پیدا نشد.');
            }

            if ($user->hasRole('scientific visitor') && (int) $sample->visit->employee_id !== (int) $user->employee?->id) {
                throw new BusinessRuleException('این نمونه متعلق به کارمند فعلی نیست.');
            }

            if ($sample->visit->status !== VisitStatus::COMPLETED) {
                throw new BusinessRuleException('فقط نمونه مربوط به بازدید تکمیل‌شده قابل ویرایش است.');
            }

            $quantity = (int) $data['quantity'];
            if ($quantity <= 0) {
                throw new BusinessRuleException('تعداد نمونه باید بیشتر از صفر باشد.');
            }

            $owner = $sample->visit->employee;
            $ownerIsScientificVisitor = $owner?->user?->hasRole('scientific visitor') ?? false;

            if ($ownerIsScientificVisitor) {
                $inventory = ScientificVisitorInventory::query()
                    ->where('employee_id', $owner->id)
                    ->where('product_id', $sample->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->used_quantity < $sample->quantity) {
                    throw new BusinessRuleException('موجودی ثبت‌شده این ویزیتور با سابقه نمونه سازگار نیست.');
                }

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