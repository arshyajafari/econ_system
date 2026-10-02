<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CustomerTransactionType;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class CustomerOpeningBalanceImportService
{
    public const SOURCE = 'customer_opening_balance_import';

    private array $statistics = [
        'processed' => 0,
        'imported' => 0,
        'skipped_empty' => 0,
        'skipped_duplicate' => 0,
        'failed' => 0,
    ];

    private array $errors = [];

    private array $seenCustomerCodes = [];

    public function __construct(
        private readonly string $openingDate,
        private readonly bool $dryRun = false,
    ) {
    }

    public function processChunk(Collection $rows, int $startRowNumber): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $normalized = [];

        foreach ($rows as $index => $row) {
            $normalized[] = [
                'row' => $startRowNumber + $index,
                'customer_id' => $this->normalizeText($this->rowValue($row, 'customer_id')),
                'balance' => $this->normalizeAmount($this->rowValue($row, 'balance')),
            ];
        }

        $codes = collect($normalized)
            ->pluck('customer_id')
            ->filter()
            ->unique()
            ->values();

        $customers = Customer::query()
            ->whereIn('code', $codes)
            ->get(['id', 'code'])
            ->keyBy('code');

        foreach ($normalized as $item) {
            $this->statistics['processed']++;

            $this->processRow(
                rowNumber: $item['row'],
                customerCode: $item['customer_id'],
                balance: $item['balance'],
                customers: $customers,
            );
        }
    }

    public function statistics(): array
    {
        return $this->statistics;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function processRow(
        int $rowNumber,
        ?string $customerCode,
        ?string $balance,
        Collection $customers,
    ): void {
        if ($customerCode === null) {
            $this->error($rowNumber, 'missing_customer_id');

            return;
        }

        if (!preg_match('/^CUS-\d{6}$/', $customerCode)) {
            $this->error($rowNumber, 'invalid_customer_id', $customerCode);

            return;
        }

        if ($balance === null) {
            $this->statistics['skipped_empty']++;

            return;
        }

        if (!$this->isValidAmount($balance)) {
            $this->error($rowNumber, 'invalid_balance', $customerCode, $balance);

            return;
        }

        if ($balance === '0' || $balance === '0.00') {
            $this->statistics['skipped_empty']++;

            return;
        }

        if (isset($this->seenCustomerCodes[$customerCode])) {
            $this->error(
                $rowNumber,
                'duplicate_customer_in_excel',
                $customerCode,
                $balance,
            );

            return;
        }

        $this->seenCustomerCodes[$customerCode] = $rowNumber;

        $customer = $customers->get($customerCode);

        if (!$customer) {
            $this->error($rowNumber, 'customer_not_found', $customerCode, $balance);

            return;
        }

        $sourceKey = $this->sourceKey((int) $customer->id);

        $alreadyImported = CustomerTransaction::query()
            ->where('source_key', $sourceKey)
            ->exists();

        if ($alreadyImported) {
            $this->statistics['skipped_duplicate']++;

            return;
        }

        if ($this->dryRun) {
            $this->statistics['imported']++;

            return;
        }

        try {
            DB::transaction(function () use ($customer, $balance, $sourceKey): void {
                CustomerTransaction::query()->create([
                    'customer_id' => $customer->id,
                    'type' => str_starts_with($balance, '-')
                        ? CustomerTransactionType::CREDIT
                        : CustomerTransactionType::DEBIT,
                    'amount' => ltrim($balance, '+-'),
                    'transaction_at' => CarbonImmutable::parse($this->openingDate)->startOfDay(),
                    'description' => 'مانده اولیه مشتری - ورود از Excel',
                    'meta' => [
                        'source' => self::SOURCE,
                        'opening_date' => $this->openingDate,
                        'customer_code' => $customer->code,
                        'original_balance' => $balance,
                    ],
                    'source_key' => $sourceKey,
                ]);
            });

            $this->statistics['imported']++;
        } catch (Throwable $exception) {
            $this->statistics['failed']++;
            $this->errors[] = [
                'row' => $rowNumber,
                'reason' => 'database_error',
                'customer_id' => $customerCode,
                'balance' => $balance,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function sourceKey(int $customerId): string
    {
        return sprintf('%s:%d:%s', self::SOURCE, $customerId, $this->openingDate);
    }

    private function rowValue(mixed $row, string $key): mixed
    {
        if ($row instanceof Collection) {
            return $row->get($key);
        }

        if (is_array($row)) {
            return $row[$key] ?? null;
        }

        return null;
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = $this->normalizeDigits(trim((string) $value));
        $value = str_replace(['٬', ',', ' '], '', $value);

        if (str_starts_with($value, '(') && str_ends_with($value, ')')) {
            $value = '-' . substr($value, 1, -1);
        }

        if (!preg_match('/^-?\d+(?:\.\d{1,2})?$/', $value)) {
            return $value;
        }

        [$integer, $decimal] = array_pad(explode('.', $value, 2), 2, '0');
        $sign = str_starts_with($integer, '-') ? '-' : '';
        $integer = ltrim($integer, '+-');
        $decimal = str_pad($decimal, 2, '0');

        return $sign . $integer . '.' . $decimal;
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    private function isValidAmount(string $amount): bool
    {
        return preg_match('/^-?\d+(?:\.\d{1,2})?$/', $amount) === 1
            && bccomp($amount, '0', 2) !== 0;
    }

    private function error(
        int $row,
        string $reason,
        ?string $customerCode = null,
        ?string $balance = null,
    ): void {
        $this->statistics['failed']++;

        $this->errors[] = [
            'row' => $row,
            'reason' => $reason,
            'customer_id' => $customerCode,
            'balance' => $balance,
        ];
    }
}
