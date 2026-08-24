<?php

    namespace App\Actions\Order;

    use App\Enums\OrderStatus;
    use App\Models\Customer;
    use App\Models\Employee;
    use App\Models\Order;
    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    class CreateOrderAction {
        public function execute(array $data): Order {
            return DB::transaction(function () use ($data) {
                $customer = Customer::query()->where('public_id', $data['customer_id'])->firstOrFail();

                $salesEmployee = Employee::query()->where('public_id', $data['sales_employee_id'])->firstOrFail();

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'sales_employee_id' => $salesEmployee->id,
                    'status' => OrderStatus::DRAFT,
                    'ordered_at' => null,
                    'description' => $data['description'] ?? null,
                ]);

                foreach ($data['items'] as $itemData) {
                    $product = Product::query()->where('public_id', $itemData['product_id'])->firstOrFail();

                    $quantity = (int)$itemData['quantity'];
                    $unitPrice = (float)$itemData['unit_price'];

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                        'description' => $itemData['description'] ?? null,
                    ]);
                }

                return $order->fresh(Order::DEFAULT_RELATIONS);
            });
        }
    }
