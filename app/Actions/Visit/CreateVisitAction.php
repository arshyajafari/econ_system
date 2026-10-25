<?php

namespace App\Actions\Visit;

use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateVisitAction
{
    public function execute(array $data, User $user): Visit
    {
        $operationId = trim((string) ($data['client_operation_id'] ?? ''));

        try {
            return DB::transaction(function () use ($data, $user, $operationId) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
                }

                if ($operationId !== '') {
                    $existingVisit = Visit::withTrashed()
                        ->where('client_operation_id', $operationId)
                        ->lockForUpdate()
                        ->first();

                    if ($existingVisit) {
                        $this->assertSameOperationPayload($existingVisit, $data, (int) $employee->id);

                        return $existingVisit->fresh(Visit::DEFAULT_RELATIONS);
                    }
                }

                $doctor = Doctor::query()->where('public_id', $data['doctor_id'])->firstOrFail();

                $visit = Visit::create([
                    'client_operation_id' => $operationId !== '' ? $operationId : null,
                    'doctor_id' => $doctor->id,
                    'employee_id' => $employee->id,
                    'status' => VisitStatus::DRAFT,
                    'visit_date' => $data['visit_date'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'location_accuracy' => $data['location_accuracy'] ?? null,
                    'location_captured_at' => $data['location_captured_at'] ?? null,
                    'purpose' => $data['purpose'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                return $visit->fresh(Visit::DEFAULT_RELATIONS);
            });
        } catch (QueryException $exception) {
            // The unique operation key is the final guard against simultaneous retries.
            if ($operationId !== '' && $this->isUniqueConstraintViolation($exception)) {
                $existingVisit = Visit::withTrashed()
                    ->where('client_operation_id', $operationId)
                    ->first();

                if ($existingVisit) {
                    $employee = $user->employee;
                    if (!$employee) {
                        throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
                    }

                    $this->assertSameOperationPayload($existingVisit, $data, (int) $employee->id);

                    return $existingVisit->fresh(Visit::DEFAULT_RELATIONS);
                }
            }

            throw $exception;
        }
    }

    private function assertSameOperationPayload(Visit $visit, array $data, int $employeeId): void
    {
        if ($visit->trashed()) {
            throw new BusinessRuleException(
                'این شناسه عملیات قبلاً به بازدید حذف‌شده اختصاص یافته است؛ برای ثبت جدید شناسه تازه‌ای ارسال کنید.'
            );
        }

        $visit->loadMissing('doctor');

        $samePayload = (int) $visit->employee_id === $employeeId
            && (string) $visit->doctor?->public_id === (string) ($data['doctor_id'] ?? '')
            && $this->sameDateTime($visit->visit_date, $data['visit_date'] ?? null)
            && $this->sameDecimal($visit->latitude, $data['latitude'] ?? null, 7)
            && $this->sameDecimal($visit->longitude, $data['longitude'] ?? null, 7)
            && $this->sameDecimal($visit->location_accuracy, $data['location_accuracy'] ?? null, 2)
            && $this->sameNullableDateTime($visit->location_captured_at, $data['location_captured_at'] ?? null)
            && $this->sameNullableString($visit->purpose, $data['purpose'] ?? null)
            && $this->sameNullableString($visit->description, $data['description'] ?? null);

        if (!$samePayload) {
            throw new BusinessRuleException(
                'شناسه عملیات قبلاً با اطلاعات متفاوت یا توسط کارمند دیگری استفاده شده است؛ برای درخواست جدید شناسه تازه‌ای ارسال کنید.'
            );
        }
    }

    private function sameDateTime(mixed $existing, mixed $requested): bool
    {
        if ($existing === null || $requested === null || $requested === '') {
            return $existing === null && ($requested === null || $requested === '');
        }

        try {
            return Carbon::parse($existing)->equalTo(Carbon::parse((string) $requested));
        } catch (\Throwable) {
            return false;
        }
    }

    private function sameNullableDateTime(mixed $existing, mixed $requested): bool
    {
        if ($existing === null || $requested === null || $requested === '') {
            return $existing === null && ($requested === null || $requested === '');
        }

        return $this->sameDateTime($existing, $requested);
    }

    private function sameDecimal(mixed $existing, mixed $requested, int $scale): bool
    {
        if ($existing === null || $requested === null || $requested === '') {
            return $existing === null && ($requested === null || $requested === '');
        }

        return number_format((float) $existing, $scale, '.', '')
            === number_format((float) $requested, $scale, '.', '');
    }

    private function sameNullableString(mixed $existing, mixed $requested): bool
    {
        $existing = $existing === '' ? null : $existing;
        $requested = $requested === '' ? null : $requested;

        return $existing === $requested;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($sqlState, ['23000', '23505', '19'], true);
    }
}
