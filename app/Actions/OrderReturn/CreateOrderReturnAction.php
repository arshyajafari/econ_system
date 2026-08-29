<?php

    namespace App\Actions\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Order;
    use App\Models\OrderReturn;
    use App\Models\User;
    use App\Services\CodeGeneratorService;
    use Illuminate\Support\Facades\DB;

    class CreateOrderReturnAction {
        public function __construct(protected CodeGeneratorService $codeGenerator) {
        }

        public function execute(array $data, User $user): OrderReturn {
            return DB::transaction(function () use ($data, $user) {
                $employee = $user->employee;

                if (!$employee) {
                    throw new BusinessRuleException('کاربر فعلی به کارمند متصل نیست.');
                }

                $order = Order::query()->where('public_id', $data['order_id'])->lockForUpdate()->with([
                    'items',
                    'returns.items',
                ])->firstOrFail();

                if ($order->status !== OrderStatus::COMPLETED) {
                    throw new BusinessRuleException('فقط سفارش تکمیل‌شده قابل برگشت است.');
                }

                if (empty($data['items'])) {
                    throw new BusinessRuleException('مرجوعی باید حداقل یک آیتم داشته باشد.');
                }

                $code = $this->codeGenerator->generate(OrderReturn::class);

                $orderReturn = OrderReturn::create([
                    'code' => $code,
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'employee_id' => $employee->id,
                    'status' => OrderReturnStatus::DRAFT,
                    'description' => $data['description'] ?? null,
                ]);

                foreach ($data['items'] as $itemData) {
                    $orderItem = $order->items->firstWhere('public_id', $itemData['order_item_id']);

                    if (!$orderItem) {
                        throw new BusinessRuleException('آیتم انتخاب‌شده متعلق به این سفارش نیست.');
                    }

                    $alreadyReturned = $order->returns->reject(fn($return) => $return->status === OrderReturnStatus::DRAFT || $return->status === OrderReturnStatus::CANCELLED)
                        ->flatMap(fn($return) => $return->items)->where('order_item_id', $orderItem->id)
                        ->sum('quantity');

                    $returnableQuantity = (int)$orderItem->quantity - (int)$alreadyReturned;

                    $quantity = (int)$itemData['quantity'];

                    if ($quantity <= 0) {
                        throw new BusinessRuleException('مقدار برگشتی باید بیشتر از صفر باشد.');
                    }

                    if ($quantity > $returnableQuantity) {
                        throw new BusinessRuleException('مقدار برگشتی بیشتر از مقدار قابل برگشت است.');
                    }

                    $unitPrice = (float)$orderItem->unit_price;

                    $orderReturn->items()->create([
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                        'description' => $itemData['description'] ?? null,
                    ]);
                }

                return $orderReturn->fresh([
                    'customer',
                    'employee',
                    'items.product',
                ]);
            });
        }
    }
