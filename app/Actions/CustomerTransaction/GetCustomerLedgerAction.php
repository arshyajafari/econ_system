<?php

    namespace App\Actions\CustomerTransaction;

    use App\Models\Customer;
    use App\Services\CustomerLedgerService;

    class GetCustomerLedgerAction {
        public function __construct(protected CustomerLedgerService $ledgerService) {
        }

        public function execute(Customer $customer, ?string $from = null, ?string $to = null): array {
            return [
                'customer' => $customer,
                ...$this->ledgerService->build(customerId: $customer->id, from: $from, to: $to),
            ];
        }
    }
