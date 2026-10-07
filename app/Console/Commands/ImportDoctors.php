<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Imports\DoctorsImport;
use App\Services\DoctorExcelImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportDoctors extends Command
{
    protected $signature = 'doctors:import
                            {file=imports/doctors.xlsx : Excel file name or path relative to storage/app or the project root}
                            {--dry-run : Validate and report without writing doctors}';

    protected $description = 'Import doctors from an Excel file';

    public function handle(): int
    {
        $file = $this->resolveFilePath((string) $this->argument('file'));

        if ($file === null) return self::FAILURE;

        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun
            ? 'Running doctor import in DRY-RUN mode...'
            : 'Running doctor import...');

        $service = new DoctorExcelImportService(dryRun: $dryRun);

        try {
            Excel::import(new DoctorsImport($service), $file);
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
                ['Skipped duplicate', $statistics['skipped_duplicate']],
                ['Failed', $statistics['failed']],
            ],
        );

        foreach ($errors as $error) {
            $this->line(sprintf(
                'Row %s | %s | name=%s %s | specialty=%s',
                $error['row'] ?? '?',
                $error['reason'] ?? 'unknown',
                $error['first_name'] ?? '-',
                $error['last_name'] ?? '-',
                $error['specialty'] ?? '-',
            ));
        }

        if ($statistics['failed'] > 0) return self::FAILURE;

        $this->info($dryRun
            ? 'Dry-run finished. No database records were written.'
            : 'Doctor import finished successfully.');

        return self::SUCCESS;
    }

    private function resolveFilePath(string $file): ?string
    {
        $file = trim($file);
        if ($file === '') {
            $this->error('Excel file path cannot be empty.');
            return null;
        }

        foreach ([
            storage_path('app/' . ltrim($file, '/\\')),
            base_path($file),
        ] as $candidate) {
            if (File::isFile($candidate)) return $candidate;
        }

        $this->error(
            'Excel file not found. Put it under storage/app or provide a valid project-relative path.'
        );

        return null;
    }
}
