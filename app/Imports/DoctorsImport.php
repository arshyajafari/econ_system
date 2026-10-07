<?php

declare(strict_types=1);

namespace App\Imports;

use App\Services\DoctorExcelImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DoctorsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $nextRowNumber = 2;

    public function __construct(
        private readonly DoctorExcelImportService $service,
    ) {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) return;

        $this->service->processChunk($rows, $this->nextRowNumber);
        $this->nextRowNumber += $rows->count();
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
