<?php

    namespace App\Actions\Order;

    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Customer;
    use App\Models\Employee;
    use App\Models\Order;
    use Illuminate\Support\Facades\DB;

    class UpdateOrderAction {
        public function __construct(protected SyncOrderItemsAction $syncOrderItems) {
        }

        public function execute(Order $order, array $data): Order {
            return DB::transaction(function () use (
                $order, $data
            ) {
                $order = Order::query()->lockForUpdate()->findOrFail($order->id);

                if ($order->status !== OrderStatus::DRAFT) {
                    throw new BusinessRuleException('فقط سفارش در وضعیت draft قابل ویرایش است.');
                }

                if (isset($data['customer_id'])) {
                    $customer = Customer::query()->where('public_id', $data['customer_id'])->firstOrFail();

                    $order->customer_id = $customer->id;
                }

                if (isset($data['sales_employee_id'])) {
                    $employee = Employee::query()->where('public_id', $data['sales_employee_id'])->firstOrFail();

                    $order->sales_employee_id = $employee->id;
                }

                if (array_key_exists('description', $data)) {
                    $order->description = $data['description'];
                }

                $order->save();

                if (array_key_exists('items', $data)) {
                    $this->syncOrderItems->execute($order, $data['items']);
                }

                return $order->fresh(Order::DEFAULT_RELATIONS);
            });
        }
    }
