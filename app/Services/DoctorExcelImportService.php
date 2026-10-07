<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DoctorSpecialty;
use App\Enums\DoctorStatus;
use App\Models\Doctor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class DoctorExcelImportService
{
    public const SOURCE = 'doctor_excel_import';

    private array $statistics = [
        'processed' => 0,
        'imported' => 0,
        'skipped_duplicate' => 0,
        'failed' => 0,
    ];

    private array $errors = [];
    private array $seenIdentities = [];
    private bool $headerChecked = false;

    public function __construct(
        private readonly bool $dryRun = false,
    ) {}

    public function processChunk(Collection $rows, int $startRowNumber): void
    {
        if ($rows->isEmpty()) return;

        if (!$this->headerChecked) {
            $this->headerChecked = true;
            $firstRow = $rows->first();
            $keys = $firstRow instanceof Collection ? array_keys($firstRow->all()) : [];
            $missingColumns = array_values(array_diff(
                ['first_name', 'last_name', 'specialty', 'phone_number'],
                $keys,
            ));

            if ($missingColumns !== []) {
                $this->statistics['failed']++;
                $this->errors[] = [
                    'row' => $startRowNumber,
                    'reason' => 'missing_required_columns',
                    'columns' => $missingColumns,
                ];
                return;
            }
        }

        foreach ($rows as $index => $row) {
            $this->statistics['processed']++;
            $this->processRow(
                $startRowNumber + $index,
                $this->normalizeName($this->rowValue($row, 'first_name')),
                $this->normalizeName($this->rowValue($row, 'last_name')),
                $this->normalizeText($this->rowValue($row, 'specialty')),
                $this->normalizePhone($this->rowValue($row, 'phone_number')),
            );
        }
    }

    public function statistics(): array { return $this->statistics; }
    public function errors(): array { return $this->errors; }

    private function processRow(
        int $rowNumber,
        ?string $firstName,
        ?string $lastName,
        ?string $specialty,
        ?string $phoneNumber,
    ): void {
        if ($firstName === null || $lastName === null || $specialty === null) {
            $this->error($rowNumber, 'missing_required_data', $firstName, $lastName, $specialty);
            return;
        }

        $specialtyEnum = DoctorSpecialty::tryFrom($specialty);
        if ($specialtyEnum === null) {
            $this->error($rowNumber, 'invalid_specialty', $firstName, $lastName, $specialty);
            return;
        }

        $identityKey = $this->identityKey($firstName, $lastName);
        if (isset($this->seenIdentities[$identityKey])) {
            $this->error($rowNumber, 'duplicate_doctor_in_excel', $firstName, $lastName, $specialty);
            return;
        }
        $this->seenIdentities[$identityKey] = $rowNumber;

        $sourceKey = $this->sourceKey($firstName, $lastName);

        $alreadyExists = Doctor::query()
            ->where('first_name', $firstName)
            ->where('last_name', $lastName)
            ->exists();

        $alreadyImported = Doctor::query()
            ->where('meta->source_key', $sourceKey)
            ->exists();

        if ($alreadyExists || $alreadyImported) {
            $this->statistics['skipped_duplicate']++;
            return;
        }

        if ($this->dryRun) {
            $this->statistics['imported']++;
            return;
        }

        try {
            DB::transaction(function () use (
                $firstName,
                $lastName,
                $specialtyEnum,
                $phoneNumber,
                $sourceKey
            ): void {
                $doctor = new Doctor();
                $doctor->first_name = $firstName;
                $doctor->last_name = $lastName;
                $doctor->specialty = $specialtyEnum;
                $doctor->phone_number = $phoneNumber;
                $doctor->status = DoctorStatus::ACTIVE;
                $doctor->is_favorite = false;
                $doctor->meta = [
                    'source' => self::SOURCE,
                    'source_key' => $sourceKey,
                    'imported_from' => 'doctors.xlsx',
                ];
                $doctor->code = Doctor::generateCode();
                $doctor->save();
            });

            $this->statistics['imported']++;
        } catch (Throwable $exception) {
            $this->statistics['failed']++;
            $this->errors[] = [
                'row' => $rowNumber,
                'reason' => 'database_error',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function sourceKey(string $firstName, string $lastName): string
    {
        return self::SOURCE . ':' . hash('sha256', $this->identityKey($firstName, $lastName));
    }

    private function identityKey(string $firstName, string $lastName): string
    {
        return mb_strtolower(
            preg_replace('/\s+/u', ' ', trim($firstName . ' ' . $lastName)) ?? '',
            'UTF-8',
        );
    }

    private function rowValue(mixed $row, string $key): mixed
    {
        return $row instanceof Collection
            ? $row->get($key)
            : (is_array($row) ? ($row[$key] ?? null) : null);
    }

    private function normalizeName(mixed $value): ?string
    {
        $value = $this->normalizeText($value);
        if ($value === null) return null;

        return trim(preg_replace('/^دکتر\s+/u', '', $value) ?? $value);
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $value = $this->normalizeText($value);
        if ($value === null) return null;

        return $this->normalizeDigits($value);
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
            '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
            '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    private function error(
        int $row,
        string $reason,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $specialty = null,
    ): void {
        $this->statistics['failed']++;
        $this->errors[] = [
            'row' => $row,
            'reason' => $reason,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'specialty' => $specialty,
        ];
    }
}
