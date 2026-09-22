<?php

namespace App\Actions\Sample;

use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Sample;
use App\Models\ScientificVisitorInventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteSampleAction
{
    public function execute(Sample $sample, User $user): void
    {
        DB::transaction(function () use ($sample, $user) {
            $sample = Sample::query()
                ->lockForUpdate()
                ->with('visit.employee.user')
                ->findOrFail($sample->id);

            if (!$sample->visit) {
                throw new BusinessRuleException('بازدید مربوط به نمونه پیدا نشد.');
            }

            if ($sample->visit->status !== VisitStatus::COMPLETED) {
                throw new BusinessRuleException('فقط نمونه مربوط به بازدید تکمیل‌شده قابل حذف است.');
            }

            if ($user->hasRole('scientific visitor') && (int) $sample->visit->employee_id !== (int) $user->employee?->id) {
                throw new BusinessRuleException('این نمونه متعلق به کارمند فعلی نیست.');
            }

            $owner = $sample->visit->employee;
            if ($owner?->user?->hasRole('scientific visitor')) {
                $inventory = ScientificVisitorInventory::query()
                    ->where('employee_id', $owner->id)
                    ->where('product_id', $sample->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    if ($inventory->used_quantity < $sample->quantity) {
                        throw new BusinessRuleException('موجودی ثبت‌شده این ویزیتور با سابقه نمونه سازگار نیست.');
                    }

                    $inventory->used_quantity -= $sample->quantity;
                    $inventory->save();
                }
            }

            $sample->delete();
        });
    }
}