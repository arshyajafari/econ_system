<?php

namespace App\Actions\CustomerTransaction;

use App\Imports\CustomerOpeningBalancesImport;
use App\Services\CustomerOpeningBalanceImportService;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportCustomerOpeningBalancesAction
{
    public function execute(
        UploadedFile $file,
        string $openingDate,
        bool $dryRun,
    ): array {
        $service = new CustomerOpeningBalanceImportService(
            openingDate: $openingDate,
            dryRun: $dryRun,
        );

        Excel::import(
            new CustomerOpeningBalancesImport($service),
            $file,
        );

        return [
            'statistics' => $service->statistics(),
            'errors' => $service->errors(),
        ];
    }
}
