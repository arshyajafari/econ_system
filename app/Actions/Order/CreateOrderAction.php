<?php

    namespace App\Actions\Order;

    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Customer;
    use App\Models\Employee;
    use App\Models\Order;
    use Illuminate\Support\Facades\DB;

    class CreateOrderAction {
        public function __construct(protected SyncOrderItemsAction $syncOrderItems) {
        }

        public function execute(array $data): Order {
            return DB::transaction(function () use ($data) {
                $customer = Customer::query()->where('public_id', $data['customer_id'])->firstOrFail();

                $salesEmployee = Employee::query()->where('public_id', $data['sales_employee_id'])->firstOrFail();

                if (empty($data['items'])) {
                    throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');
                }

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'sales_employee_id' => $salesEmployee->id,
                    'status' => OrderStatus::DRAFT,
                    'ordered_at' => null,
                    'description' => $data['description'] ?? null,
                ]);

                $this->syncOrderItems->execute($order, $data['items']);

                return $order->fresh(Order::DEFAULT_RELATIONS);
            });
        }
    }
