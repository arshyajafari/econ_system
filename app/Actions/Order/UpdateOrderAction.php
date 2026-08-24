<?php

    namespace App\Actions\Order;

    use App\Enums\OrderStatus;
    use App\Models\Customer;
    use App\Models\Employee;
    use App\Models\Order;
    use App\Models\Product;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class UpdateOrderAction {
        public function execute(Order $order, array $data): Order {
            return DB::transaction(function () use ($order, $data) {
                $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

                if ($order->status !== OrderStatus::DRAFT) {
                    throw new RuntimeException('فقط سفارش در وضعیت draft قابل ویرایش است.');
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
                    $order->items()->delete();

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
                }

                return $order->fresh(Order::DEFAULT_RELATIONS);
            });
        }
    }
