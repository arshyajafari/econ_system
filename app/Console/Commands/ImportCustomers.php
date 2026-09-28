<?php

    declare(strict_types=1);

    namespace App\Console\Commands;

    use App\Imports\CustomersImport;
    use App\Services\CustomerImportService;
    use Illuminate\Console\Command;
    use Illuminate\Support\Facades\File;
    use Maatwebsite\Excel\Facades\Excel;
    use Throwable;

    class ImportCustomers extends Command {
        protected $signature = 'customers:import
                            {file : Path to the Excel XLSX file}
                            {--dry-run : Validate and detect duplicates without inserting anything}
                            {--limit= : Process only the first N data rows}';

        protected $description = 'Import customers from an Excel XLSX file';

        public function handle(): int {
            $file = $this->resolveFilePath($this->argument('file'));

            if ($file === null) {
                $this->error('Excel file not found.');

                return self::FAILURE;
            }

            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'xlsx') {
                $this->error('Only XLSX files are supported.');

                return self::FAILURE;
            }

            $limit = $this->option('limit');

            if ($limit !== null) {
                if (!ctype_digit((string)$limit) || (int)$limit < 1) {
                    $this->error('--limit must be a positive integer.');

                    return self::FAILURE;
                }

                $limit = (int)$limit;
            }

            $dryRun = (bool)$this->option('dry-run');

            $this->newLine();

            $this->info('Customer Excel Import');
            $this->line('----------------------');
            $this->line("File: {$file}");
            $this->line('Mode: ' . ($dryRun ? 'DRY RUN' : 'REAL IMPORT'));

            if ($limit !== null) {
                $this->line("Limit: {$limit} rows");
            }

            $this->newLine();

            if (!$dryRun) {
                $this->warn('REAL IMPORT MODE: records will be inserted into the customers table.');
                $this->newLine();

                if (!$this->confirm('Do you want to continue?', false)) {
                    $this->info('Import cancelled.');

                    return self::SUCCESS;
                }

                $this->newLine();
            }

            $service = new CustomerImportService(dryRun: $dryRun,);

            try {
                Excel::import(new CustomersImport(service: $service, limit: $limit,), $file);
            } catch (Throwable $exception) {
                $this->error('Import process failed.');
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $statistics = $service->statistics();
            $errors = $service->errors();

            $this->displaySummary(statistics: $statistics, dryRun: $dryRun);

            if ($errors !== []) {
                $reportPath = $this->writeReport(errors: $errors, file: $file, dryRun: $dryRun);

                $this->newLine();
                $this->warn("Detailed report: {$reportPath}");
            }

            $this->newLine();

            if ($statistics['failed'] > 0) {
                $this->error('Import completed with database errors.');

                return self::FAILURE;
            }

            $this->info($dryRun ? 'Dry-run completed successfully. No database records were inserted.' : 'Customer import completed.');

            return self::SUCCESS;
        }

        /**
         * Resolve relative or absolute file paths.
         */
        private function resolveFilePath(string $path): ?string {
            $path = trim($path);

            if ($path === '') {
                return null;
            }

            if (File::exists($path)) {
                return realpath($path) ?: $path;
            }

            $projectPath = base_path($path);

            if (File::exists($projectPath)) {
                return realpath($projectPath) ?: $projectPath;
            }

            return null;
        }

        /**
         * Display import summary.
         *
         * @param array<string, int> $statistics
         */
        private function displaySummary(array $statistics, bool $dryRun): void {
            $this->newLine();

            $this->info('Import Summary');
            $this->line('--------------');

            $this->table([
                    'Metric',
                    'Count'
                ], [
                    [
                        'Processed',
                        $statistics['processed']
                    ],
                    [
                        $dryRun ? 'Would Import' : 'Imported',
                        $statistics['imported'],
                    ],
                    [
                        'Skipped',
                        $statistics['skipped']
                    ],
                    [
                        'Failed',
                        $statistics['failed']
                    ],
                ]);
        }

        /**
         * Write a detailed JSON report.
         *
         * @param array<int, array<string, mixed>> $errors
         */
        private function writeReport(array $errors, string $file, bool $dryRun): string {
            $directory = storage_path('app/imports/reports');

            File::ensureDirectoryExists($directory);

            $timestamp = now()->format('Ymd_His');

            $mode = $dryRun ? 'dry_run' : 'import';

            $reportFile = "{$directory}/customers_{$mode}_{$timestamp}.json";

            $report = [
                'generated_at' => now()->toIso8601String(),
                'mode' => $dryRun ? 'dry-run' : 'import',
                'source_file' => $file,
                'errors' => $errors,
            ];

            File::put($reportFile,
                json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $reportFile;
        }
    }
