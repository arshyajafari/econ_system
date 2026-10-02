<?php

namespace App\Imports;

use App\Services\CustomerOpeningBalanceImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerOpeningBalancesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $nextRowNumber = 2;

    public function __construct(
        private readonly CustomerOpeningBalanceImportService $service,
    ) {
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $this->service->processChunk(
            rows: $rows,
            startRowNumber: $this->nextRowNumber,
        );

        $this->nextRowNumber += $rows->count();
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
