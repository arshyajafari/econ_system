<?php

    declare(strict_types=1);

    namespace App\Imports;

    use App\Services\CustomerImportService;
    use Illuminate\Support\Collection;
    use Maatwebsite\Excel\Concerns\ToCollection;
    use Maatwebsite\Excel\Concerns\WithChunkReading;
    use Maatwebsite\Excel\Concerns\WithHeadingRow;

    class CustomersImport implements ToCollection, WithHeadingRow, WithChunkReading {
        private int $nextRowNumber = 2;

        public function __construct(private readonly CustomerImportService $service,
            private readonly ?int $limit = null,) {
        }

        /**
         * Process an Excel chunk.
         */
        public function collection(Collection $rows): void {
            if ($rows->isEmpty()) {
                return;
            }

            if ($this->limit !== null) {
                $remaining = $this->limit - $this->service->statistics()['processed'];

                if ($remaining <= 0) {
                    return;
                }

                if ($rows->count() > $remaining) {
                    $rows = $rows->take($remaining);
                }
            }

            $startRowNumber = $this->nextRowNumber;

            $this->service->processChunk(rows: $rows, startRowNumber: $startRowNumber);

            $this->nextRowNumber += $rows->count();
        }

        /**
         * Read the spreadsheet in manageable chunks.
         */
        public function chunkSize(): int {
            return 500;
        }
    }
