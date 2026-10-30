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

            // Samples can be corrected while a visit is active or completed.
            // A cancelled visit has already released its inventory and must not
            // release it a second time through sample deletion.
            if ($sample->visit->status === VisitStatus::CANCELLED) {
                throw new BusinessRuleException('نمونه مربوط به بازدید لغوشده قابل حذف نیست؛ موجودی آن قبلاً آزاد شده است.');
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

                if (!$inventory || (int) $inventory->used_quantity < (int) $sample->quantity) {
                    throw new BusinessRuleException('موجودی ثبت‌شده این ویزیتور با سابقه نمونه سازگار نیست؛ حذف انجام نشد.');
                }

                app(\App\Actions\ScientificVisitorInventory\AdjustScientificVisitorInventoryUsageAction::class)
                    ->execute($inventory, -(int) $sample->quantity);
            }

            $sample->delete();
        });
    }
}
