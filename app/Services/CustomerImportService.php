<?php

    declare(strict_types=1);

    namespace App\Services;

    use App\Enums\CustomerStatus;
    use App\Enums\CustomerType;
    use App\Models\Customer;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\DB;
    use Throwable;

    class CustomerImportService {
        /**
         * Identity values already seen in the current Excel file.
         *
         * @var array<string, int>
         */
        private array $seenNationalCodes = [];

        /**
         * @var array<string, int>
         */
        private array $seenPhoneNumbers = [];

        /**
         * Import statistics.
         *
         * @var array<string, int>
         */
        private array $statistics = [
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        /**
         * Import errors and skipped rows.
         *
         * @var array<int, array<string, mixed>>
         */
        private array $errors = [];

        public function __construct(private readonly bool $dryRun = false) {
        }

        /**
         * Process one Excel chunk.
         */
        public function processChunk(Collection $rows, int $startRowNumber): void {
            if ($rows->isEmpty()) {
                return;
            }

            $normalizedRows = [];

            foreach ($rows as $index => $row) {
                $excelRowNumber = $startRowNumber + $index;

                $normalizedRows[] = [
                    'row_number' => $excelRowNumber,
                    'data' => $this->normalizeRow($row),
                ];
            }

            $existingCustomers = $this->findExistingCustomers($normalizedRows);

            foreach ($normalizedRows as $item) {
                $this->statistics['processed']++;

                $this->processRow(rowNumber: $item['row_number'], data: $item['data'],
                    existingCustomers: $existingCustomers,);
            }
        }

        /**
         * Return import statistics.
         *
         * @return array<string, int>
         */
        public function statistics(): array {
            return $this->statistics;
        }

        /**
         * Return import errors/skipped rows.
         *
         * @return array<int, array<string, mixed>>
         */
        public function errors(): array {
            return $this->errors;
        }

        /**
         * Normalize a single Excel row.
         *
         * @param mixed $row
         * @return array<string, ?string>
         */
        private function normalizeRow(mixed $row): array {
            return [
                'customer_name' => $this->normalizeText($this->rowValue($row, 'customer_name')),

                'national_code' => $this->normalizeNationalCode($this->rowValue($row, 'national_code')),

                'telephone_number' => $this->normalizeTelephone($this->rowValue($row, 'telephone_number')),

                'phone_number' => $this->normalizePhone($this->rowValue($row, 'phone_number')),
            ];
        }

        /**
         * Read a value from a heading-row collection.
         */
        private function rowValue(mixed $row, string $key): mixed {
            if ($row instanceof Collection) {
                return $row->get($key);
            }

            if (is_array($row)) {
                return $row[$key] ?? null;
            }

            return null;
        }

        /**
         * Normalize general text.
         */
        private function normalizeText(mixed $value): ?string {
            if ($value === null) {
                return null;
            }

            $value = trim((string)$value);

            if ($value === '') {
                return null;
            }

            return preg_replace('/\s+/u', ' ', $value) ?: null;
        }

        /**
         * Normalize Persian/Arabic digits into English digits.
         */
        private function normalizeDigits(string $value): string {
            return strtr($value, [
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',

                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
            ]);
        }

        /**
         * Normalize national code.
         */
        private function normalizeNationalCode(mixed $value): ?string {
            if ($value === null) {
                return null;
            }

            $value = $this->normalizeDigits(trim((string)$value));

            if ($value === '') {
                return null;
            }

            if (preg_match('/^(\d+)\.0$/', $value, $matches)) {
                $value = $matches[1];
            }

            if (!preg_match('/^\d+$/', $value)) {
                return $value;
            }

            return $value;
        }

        /**
         * Normalize a mobile phone number.
         */
        private function normalizePhone(mixed $value): ?string {
            if ($value === null) {
                return null;
            }

            $value = $this->normalizeDigits(trim((string)$value));

            if ($value === '') {
                return null;
            }

            if (preg_match('/^(\d+)\.0$/', $value, $matches)) {
                $value = $matches[1];
            }

            $value = preg_replace('/[\s\-\(\)\.]/u', '', $value) ?? $value;

            if (str_starts_with($value, '+98')) {
                $value = '0' . substr($value, 3);
            } elseif (str_starts_with($value, '0098')) {
                $value = '0' . substr($value, 4);
            } elseif (str_starts_with($value, '98') && strlen($value) >= 11) {
                $value = '0' . substr($value, 2);
            }

            if (strlen($value) === 10 && str_starts_with($value, '9')) {
                $value = '0' . $value;
            }

            return $value;
        }

        /**
         * Normalize telephone number.
         */
        private function normalizeTelephone(mixed $value): ?string {
            if ($value === null) {
                return null;
            }

            $value = $this->normalizeDigits(trim((string)$value));

            if ($value === '') {
                return null;
            }

            if (preg_match('/^(\d+)\.0$/', $value, $matches)) {
                $value = $matches[1];
            }

            $value = preg_replace('/\D/u', '', $value) ?? $value;

            return $value !== '' ? $value : null;
        }

        /**
         * Find existing customers matching any national code or phone
         * appearing in the current chunk.
         *
         * @param array<int, array{row_number:int,data:array<string,?string>}> $rows
         * @return Collection<int, Customer>
         */
        private function findExistingCustomers(array $rows): Collection {
            $nationalCodes = [];
            $phoneNumbers = [];

            foreach ($rows as $row) {
                $data = $row['data'];

                if ($data['national_code'] !== null) {
                    $nationalCodes[] = $data['national_code'];
                }

                if ($data['phone_number'] !== null) {
                    $phoneNumbers[] = $data['phone_number'];
                }
            }

            $nationalCodes = array_values(array_unique($nationalCodes));
            $phoneNumbers = array_values(array_unique($phoneNumbers));

            if ($nationalCodes === [] && $phoneNumbers === []) {
                return collect();
            }

            return Customer::query()->withTrashed()->where(function ($query) use ($nationalCodes, $phoneNumbers) {
                if ($nationalCodes !== []) {
                    $query->whereIn('national_code', $nationalCodes);
                }

                if ($phoneNumbers !== []) {
                    $method = $nationalCodes !== [] ? 'orWhereIn' : 'whereIn';

                    $query->{$method}('phone_number', $phoneNumbers);
                }
            })->get([
                'id',
                'customer_name',
                'national_code',
                'phone_number',
                'deleted_at',
            ]);
        }

        /**
         * Process one normalized row.
         *
         * @param array<string, ?string> $data
         * @param Collection<int, Customer> $existingCustomers
         */
        private function processRow(int $rowNumber, array $data, Collection $existingCustomers): void {
            $validationError = $this->validateRow($data);

            if ($validationError !== null) {
                $this->skip(rowNumber: $rowNumber, reason: $validationError, data: $data);

                return;
            }

            $fileDuplicate = $this->checkFileDuplicate($rowNumber, $data);

            if ($fileDuplicate !== null) {
                $this->skip(rowNumber: $rowNumber, reason: $fileDuplicate, data: $data);

                return;
            }

            $databaseResult = $this->checkDatabaseDuplicate(data: $data, existingCustomers: $existingCustomers);

            if ($databaseResult !== null) {
                $this->skip(rowNumber: $rowNumber, reason: $databaseResult, data: $data);

                return;
            }

            $this->rememberIdentity($rowNumber, $data);

            if ($this->dryRun) {
                $this->statistics['imported']++;

                return;
            }

            try {
                DB::transaction(function () use ($data): void {
                    $customer = new Customer();

                    $customer->code = Customer::generateCode();
                    $customer->customer_name = $data['customer_name'];
                    $customer->type = CustomerType::PHARMACY;
                    $customer->owner_name = null;
                    $customer->manager_name = null;
                    $customer->economic_code = null;
                    $customer->national_code = $data['national_code'];
                    $customer->phone_number = $data['phone_number'];
                    $customer->telephone_number = $data['telephone_number'];
                    $customer->social_link = null;
                    $customer->birth_date = null;
                    $customer->status = CustomerStatus::ACTIVE;
                    $customer->attachment = null;
                    $customer->description = null;
                    $customer->meta = null;

                    $customer->save();
                });

                $this->statistics['imported']++;
            } catch (Throwable $exception) {
                $this->statistics['failed']++;

                $this->errors[] = [
                    'row' => $rowNumber,
                    'reason' => 'database_error',
                    'message' => $exception->getMessage(),
                    'customer_name' => $data['customer_name'],
                    'national_code' => $data['national_code'],
                    'phone_number' => $data['phone_number'],
                    'telephone_number' => $data['telephone_number'],
                ];
            }
        }

        /**
         * Validate normalized row.
         */
        private function validateRow(array $data): ?string {
            if ($data['customer_name'] === null) {
                return 'missing_customer_name';
            }

            if (mb_strlen($data['customer_name']) > 300) {
                return 'customer_name_too_long';
            }

            if ($data['national_code'] === null && $data['phone_number'] === null) {
                return 'missing_identity';
            }

            if ($data['national_code'] !== null && !preg_match('/^\d+$/', $data['national_code'])) {
                return 'invalid_national_code';
            }

            if ($data['national_code'] !== null && strlen($data['national_code']) > 25) {
                return 'national_code_too_long';
            }

            if ($data['phone_number'] !== null && !preg_match('/^\d+$/', $data['phone_number'])) {
                return 'invalid_phone_number';
            }

            if ($data['phone_number'] !== null && (strlen($data['phone_number']) < 7 || strlen($data['phone_number']) > 20)) {
                return 'phone_number_length_invalid';
            }

            if ($data['telephone_number'] !== null && strlen($data['telephone_number']) > 20) {
                return 'telephone_number_too_long';
            }

            return null;
        }

        /**
         * Detect duplicates within the current Excel file.
         */
        private function checkFileDuplicate(int $rowNumber, array $data): ?string {
            $nationalRow = null;
            $phoneRow = null;

            if ($data['national_code'] !== null) {
                $nationalRow = $this->seenNationalCodes[$data['national_code']] ?? null;
            }

            if ($data['phone_number'] !== null) {
                $phoneRow = $this->seenPhoneNumbers[$data['phone_number']] ?? null;
            }

            if ($nationalRow !== null && $phoneRow !== null) {
                if ($nationalRow === $phoneRow) {
                    return "duplicate_in_excel_same_row_{$nationalRow}";
                }

                return "conflicting_identity_in_excel_rows_{$nationalRow}_and_{$phoneRow}";
            }

            if ($nationalRow !== null) {
                return "duplicate_national_code_in_excel_row_{$nationalRow}";
            }

            if ($phoneRow !== null) {
                return "duplicate_phone_number_in_excel_row_{$phoneRow}";
            }

            return null;
        }

        /**
         * Detect duplicates/conflicts against existing DB customers.
         */
        private function checkDatabaseDuplicate(array $data, Collection $existingCustomers): ?string {
            $nationalMatches = collect();
            $phoneMatches = collect();

            if ($data['national_code'] !== null) {
                $nationalMatches = $existingCustomers->where('national_code', $data['national_code']);
            }

            if ($data['phone_number'] !== null) {
                $phoneMatches = $existingCustomers->where('phone_number', $data['phone_number']);
            }

            if ($nationalMatches->isEmpty() && $phoneMatches->isEmpty()) {
                return null;
            }

            if ($nationalMatches->count() > 1) {
                return 'multiple_customers_match_national_code';
            }

            if ($phoneMatches->count() > 1) {
                return 'multiple_customers_match_phone_number';
            }

            $nationalCustomer = $nationalMatches->first();
            $phoneCustomer = $phoneMatches->first();

            if ($nationalCustomer !== null && $phoneCustomer !== null) {
                if ($nationalCustomer->id !== $phoneCustomer->id) {
                    return 'national_code_and_phone_match_different_customers';
                }

                if ($nationalCustomer->deleted_at !== null) {
                    return 'matches_soft_deleted_customer';
                }

                return 'customer_already_exists';
            }

            $matchedCustomer = $nationalCustomer ?? $phoneCustomer;

            if ($matchedCustomer?->deleted_at !== null) {
                return 'matches_soft_deleted_customer';
            }

            return 'customer_already_exists';
        }

        /**
         * Remember identity values from a valid row.
         */
        private function rememberIdentity(int $rowNumber, array $data): void {
            if ($data['national_code'] !== null) {
                $this->seenNationalCodes[$data['national_code']] = $rowNumber;
            }

            if ($data['phone_number'] !== null) {
                $this->seenPhoneNumbers[$data['phone_number']] = $rowNumber;
            }
        }

        /**
         * Register a skipped row.
         */
        private function skip(int $rowNumber, string $reason, array $data): void {
            $this->statistics['skipped']++;

            $this->errors[] = [
                'row' => $rowNumber,
                'reason' => $reason,
                'customer_name' => $data['customer_name'],
                'national_code' => $data['national_code'],
                'phone_number' => $data['phone_number'],
                'telephone_number' => $data['telephone_number'],
            ];
        }
    }
