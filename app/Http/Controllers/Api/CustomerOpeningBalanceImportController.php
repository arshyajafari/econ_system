<?php

namespace App\Http\Controllers\Api;

use App\Actions\CustomerTransaction\ImportCustomerOpeningBalancesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerTransaction\ImportCustomerOpeningBalancesRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class CustomerOpeningBalanceImportController extends Controller
{
    public function store(
        ImportCustomerOpeningBalancesRequest $request,
        ImportCustomerOpeningBalancesAction $action,
    ): JsonResponse {
        $this->authorize('create', Customer::class);

        $data = $request->validated();

        $result = $action->execute(
            file: $data['file'],
            openingDate: $data['opening_date'],
            dryRun: $data['mode'] === 'preview',
        );

        return response()->json($result);
    }
}
