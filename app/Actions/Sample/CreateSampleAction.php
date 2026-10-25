<?php

namespace App\Actions\Sample;

use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\Sample;
use App\Models\ScientificVisitorInventory;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateSampleAction
{
    public function execute(array $data, User $user): Sample
    {
        $operationId = trim((string) ($data['client_operation_id'] ?? ''));

        try {
            return DB::transaction(function () use ($data, $user, $operationId) {
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

                $quantity = (int) $data['quantity'];
                if ($quantity <= 0) {
                    throw new BusinessRuleException('تعداد نمونه باید بیشتر از صفر باشد.');
                }

                $description = $data['description'] ?? null;
                $requestHash = $this->requestHash(
                    (string) $visit->public_id,
                    (string) $data['product_id'],
                    $quantity,
                    $description
                );

                if ($operationId !== '') {
                    $operation = DB::table('sample_operations')
                        ->where('client_operation_id', $operationId)
                        ->lockForUpdate()
                        ->first();

                    if ($operation) {
                        return $this->resolveRecordedOperation($operation, $requestHash);
                    }

                    // Compatibility for operations created before the operation ledger existed.
                    $legacySample = Sample::query()
                        ->with(Sample::DEFAULT_RELATIONS)
                        ->where('client_operation_id', $operationId)
                        ->first();

                    if ($legacySample) {
                        if ((int) $legacySample->visit_id !== (int) $visit->id
                            || (string) $legacySample->product?->public_id !== (string) $data['product_id']) {
                            throw new BusinessRuleException(
                                'شناسه عملیات قبلاً برای بازدید یا محصول دیگری استفاده شده است.'
                            );
                        }

                        return $legacySample;
                    }
                }

                if ($visit->status === VisitStatus::CANCELLED) {
                    throw new BusinessRuleException('برای بازدید لغوشده نمی‌توان نمونه ثبت کرد.');
                }

                $product = Product::query()
                    ->where('public_id', $data['product_id'])
                    ->firstOrFail();

                if ($user->hasRole('scientific visitor')) {
                    $inventory = ScientificVisitorInventory::query()
                        ->where('employee_id', $employee->id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory || $inventory->available_quantity < $quantity) {
                        throw new BusinessRuleException('موجودی این محصول برای ثبت نمونه کافی نیست.');
                    }

                    app(\App\Actions\ScientificVisitorInventory\AdjustScientificVisitorInventoryUsageAction::class)
                        ->execute($inventory, $quantity);
                }

                // Soft-deleted rows still occupy the unique visit/product key.
                $sample = Sample::withTrashed()
                    ->where('visit_id', $visit->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if ($sample?->trashed()) {
                    throw new BusinessRuleException(
                        'برای این محصول در این بازدید سابقه حذف‌شده وجود دارد؛ نمونه را از مسیر ویرایش سوابق بازیابی کنید.'
                    );
                }

                if ($sample) {
                    $sample->quantity += $quantity;

                    if (array_key_exists('description', $data) && $data['description'] !== null) {
                        $sample->description = $data['description'];
                    }

                    $sample->save();
                } else {
                    $sample = Sample::create([
                        'client_operation_id' => $operationId !== '' ? $operationId : null,
                        'visit_id' => $visit->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'description' => $description,
                    ]);
                }

                if ($operationId !== '') {
                    DB::table('sample_operations')->insert([
                        'client_operation_id' => $operationId,
                        'visit_id' => $visit->id,
                        'product_id' => $product->id,
                        'sample_id' => $sample->id,
                        'quantity' => $quantity,
                        'request_hash' => $requestHash,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $sample->fresh(Sample::DEFAULT_RELATIONS);
            });
        } catch (QueryException $exception) {
            // Concurrent requests can race to insert the same unique operation ID.
            // The losing transaction is rolled back, then resolves the committed winner.
            if ($operationId !== '' && $this->isUniqueConstraintViolation($exception)) {
                $operation = DB::table('sample_operations')
                    ->where('client_operation_id', $operationId)
                    ->first();

                if ($operation) {
                    $quantity = (int) $data['quantity'];
                    $requestHash = $this->requestHash(
                        (string) $data['visit_id'],
                        (string) $data['product_id'],
                        $quantity,
                        $data['description'] ?? null
                    );

                    return $this->resolveRecordedOperation($operation, $requestHash);
                }
            }

            throw $exception;
        }
    }

    private function resolveRecordedOperation(object $operation, string $requestHash): Sample
    {
        if (!hash_equals((string) $operation->request_hash, $requestHash)) {
            throw new BusinessRuleException(
                'شناسه عملیات قبلاً با اطلاعات متفاوت استفاده شده است؛ برای درخواست جدید شناسه تازه‌ای ارسال کنید.'
            );
        }

        $sample = Sample::withTrashed()->find($operation->sample_id);

        if (!$sample || $sample->trashed()) {
            throw new BusinessRuleException(
                'این عملیات قبلاً ثبت شده، اما نمونه مربوط به آن دیگر فعال نیست؛ درخواست را دوباره ثبت نکنید.'
            );
        }

        return $sample->fresh(Sample::DEFAULT_RELATIONS);
    }

    private function requestHash(
        string $visitPublicId,
        string $productPublicId,
        int $quantity,
        ?string $description
    ): string {
        return hash('sha256', json_encode([
            'visit_id' => $visitPublicId,
            'product_id' => $productPublicId,
            'quantity' => $quantity,
            'description' => $description,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($sqlState, ['23000', '23505', '19'], true);
    }
}
