<?php

    namespace App\Actions\Order;

    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Customer;
    use App\Models\Order;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;

    class CreateOrderAction {
        public function __construct(protected SyncOrderItemsAction $syncOrderItems) {
        }

        public function execute(array $data, User $user): Order {
            return DB::transaction(function () use ($data, $user) {
                $customer = Customer::query()->where('public_id', $data['customer_id'])->firstOrFail();

                $salesEmployee = $user->employee;

                if (!$salesEmployee) {
                    throw new BusinessRuleException('کاربر واردشده به کارمند متصل نیست و امکان ثبت سفارش وجود ندارد.');
                }

                if (empty($data['items'])) {
                    throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');
                }

                $order = Order::create([
                    'code' => Order::generateCode(),
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
