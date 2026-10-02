<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Imports\CustomerOpeningBalancesImport;
use App\Services\CustomerOpeningBalanceImportService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportCustomerOpeningBalances extends Command
{
    protected $signature = 'customers:import-opening-balances
                            {file=customer-opening-balances.xlsx : Excel file name or path}
                            {--date= : Opening balance date in Y-m-d format}
                            {--dry-run : Validate and report without writing transactions}';

    protected $description = 'Import customer opening balances from an Excel file before production startup';

    public function handle(): int
    {
        $date = (string) $this->option('date');

        if ($date === '') {
            $this->error('The --date option is required. Example: --date=2026-09-30');

            return self::FAILURE;
        }

        try {
            $openingDate = CarbonImmutable::createFromFormat('Y-m-d', $date);
        } catch (Throwable) {
            $this->error('Invalid date. Use Y-m-d format, for example 2026-09-30.');

            return self::FAILURE;
        }

        if ($openingDate->format('Y-m-d') !== $date) {
            $this->error('Invalid date. Use Y-m-d format, for example 2026-09-30.');

            return self::FAILURE;
        }

        $file = $this->resolveFilePath((string) $this->argument('file'));

        if ($file === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Running customer opening balance import in DRY-RUN mode...'
            : 'Running customer opening balance import...'
        );

        $service = new CustomerOpeningBalanceImportService(
            openingDate: $openingDate->format('Y-m-d'),
            dryRun: $dryRun,
        );

        try {
            Excel::import(
                new CustomerOpeningBalancesImport($service),
                $file,
            );
        } catch (Throwable $exception) {
            $this->error('Import failed: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $statistics = $service->statistics();
        $errors = $service->errors();

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $statistics['processed']],
                ['Imported', $statistics['imported']],
                ['Skipped empty/zero', $statistics['skipped_empty']],
                ['Skipped duplicate', $statistics['skipped_duplicate']],
                ['Failed', $statistics['failed']],
            ],
        );

        if ($errors !== []) {
            $this->newLine();
            $this->warn('Import completed with validation errors:');

            foreach ($errors as $error) {
                $row = $error['row'] ?? '?';
                $reason = $error['reason'] ?? 'unknown';
                $customerId = $error['customer_id'] ?? '-';
                $balance = $error['balance'] ?? '-';

                $this->line(
                    sprintf(
                        'Row %s | %s | customer_id=%s | balance=%s',
                        $row,
                        $reason,
                        $customerId,
                        $balance,
                    ),
                );
            }
        }

        if ($statistics['failed'] > 0) {
            return self::FAILURE;
        }

        $this->info($dryRun
            ? 'Dry-run finished. No database records were written.'
            : 'Customer opening balance import finished successfully.'
        );

        return self::SUCCESS;
    }

    private function resolveFilePath(string $file): ?string
    {
        $file = trim($file);

        if ($file === '') {
            $this->error('Excel file path cannot be empty.');

            return null;
        }

        $candidates = [
            storage_path('app/' . ltrim($file, '/\\')),
            base_path($file),
        ];

        foreach ($candidates as $candidate) {
            if (File::isFile($candidate)) {
                return $candidate;
            }
        }

        $this->error(
            'Excel file not found. Put it under storage/app or provide a valid project-relative path.'
        );

        return null;
    }
}
