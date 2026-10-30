<?php

namespace App\Actions\Visit;

use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Sample;
use App\Models\ScientificVisitorInventory;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateVisitWithSamplesAction
{
    public function execute(Visit $visit, array $data, User $user): Visit
    {
        return DB::transaction(function () use ($visit, $data, $user) {
            $visit = Visit::query()
                ->lockForUpdate()
                ->with('employee.user')
                ->findOrFail($visit->id);

            if ($visit->status !== VisitStatus::DRAFT) {
                throw new BusinessRuleException('فقط بازدید در وضعیت پیش‌نویس قابل ویرایش است.');
            }

            $submittedSamples = collect($data['samples'] ?? []);
            $existingSamples = Sample::query()
                ->where('visit_id', $visit->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('public_id');

            if ($submittedSamples->count() !== $existingSamples->count()
                || $submittedSamples->pluck('id')->unique()->count() !== $existingSamples->count()
                || $submittedSamples->pluck('id')->diff($existingSamples->keys())->isNotEmpty()) {
                throw new BusinessRuleException('فهرست نمونه‌ها تغییر کرده است. صفحه را تازه‌سازی کرده و دوباره تلاش کنید.');
            }

            $owner = $visit->employee;
            $ownerIsScientificVisitor = $owner?->user?->hasRole('scientific visitor') ?? false;

            foreach ($submittedSamples as $change) {
                $sample = $existingSamples->get($change['id']);
                Gate::forUser($user)->authorize('update', $sample);
                $quantity = (int) $change['quantity'];

                if ($quantity <= 0) {
                    throw new BusinessRuleException('تعداد نمونه باید بیشتر از صفر باشد.');
                }

                if ($ownerIsScientificVisitor && $quantity !== (int) $sample->quantity) {
                    $inventory = ScientificVisitorInventory::query()
                        ->where('employee_id', $owner->id)
                        ->where('product_id', $sample->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) {
                        throw new BusinessRuleException('موجودی ویزیتور برای این محصول پیدا نشد.');
                    }

                    app(\App\Actions\ScientificVisitorInventory\AdjustScientificVisitorInventoryUsageAction::class)
                        ->execute($inventory, $quantity - (int) $sample->quantity);
                }

                $sample->update([
                    'quantity' => $quantity,
                    'description' => $change['description'] ?? null,
                ]);
            }

            $visit->update([
                'visit_date' => $data['visit_date'],
                'purpose' => $data['purpose'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            return $visit->fresh(Visit::DEFAULT_RELATIONS);
        });
    }
}
