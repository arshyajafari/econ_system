<?php

namespace App\Http\Controllers\Api;

use App\Actions\CustomerTransaction\GetCustomerLedgerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerTransaction\CustomerLedgerRequest;
use App\Http\Resources\CustomerLedgerResource;
use App\Models\Customer;

class CustomerLedgerController extends Controller
{
    public function show(
        CustomerLedgerRequest $request,
        Customer $customer,
        GetCustomerLedgerAction $action,
    ): CustomerLedgerResource {
        $this->authorize('view', $customer);

        $data = $request->validated();

        return new CustomerLedgerResource(
            $action->execute($customer, $data['from'] ?? null, $data['to'] ?? null),
        );
    }
}
