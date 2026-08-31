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

                if (empty($data['items'])) {
                    throw new BusinessRuleException('مرجوعی باید حداقل یک آیتم داشته باشد.');
                }

                $order = Order::query()->where('public_id', $data['order_id'])->lockForUpdate()->with([
                    'items',
                    'returns.items',
                ])->firstOrFail();

                if ($order->status !== OrderStatus::COMPLETED) {
                    throw new BusinessRuleException('فقط سفارش تکمیل‌شده قابل برگشت است.');
                }

                /*
                 * Aggregate requested quantities first.
                 *
                 * This prevents the same OrderItem from appearing
                 * multiple times in the same return and bypassing
                 * the returnable-quantity limit.
                 */
                $requestedQuantities = collect($data['items'])->groupBy('order_item_id')
                    ->map(fn($items) => $items->sum(fn($item) => (int)$item['quantity']));

                foreach ($requestedQuantities as $orderItemPublicId => $quantity) {
                    if ($quantity <= 0) {
                        throw new BusinessRuleException('مقدار برگشتی باید بیشتر از صفر باشد.');
                    }

                    $orderItem = $order->items->firstWhere('public_id', $orderItemPublicId);

                    if (!$orderItem) {
                        throw new BusinessRuleException('آیتم انتخاب‌شده متعلق به این سفارش نیست.');
                    }

                    $alreadyReturned = $order->returns->reject(fn($return) => $return->status === OrderReturnStatus::DRAFT || $return->status === OrderReturnStatus::CANCELLED)
                        ->flatMap(fn($return) => $return->items)->where('order_item_id', $orderItem->id)
                        ->sum('quantity');

                    $returnableQuantity = (int)$orderItem->quantity - (int)$alreadyReturned;

                    if ($quantity > $returnableQuantity) {
                        throw new BusinessRuleException('مقدار برگشتی بیشتر از مقدار قابل برگشت است.');
                    }
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

                    $orderReturn->items()->create([
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'quantity' => (int)$itemData['quantity'],
                        'unit_price' => (float)$orderItem->unit_price,
                        'total_price' => (int)$itemData['quantity'] * (float)$orderItem->unit_price,
                        'description' => $itemData['description'] ?? null,
                    ]);
                }

                return $orderReturn->fresh([
                    'customer',
                    'employee',
                    'items.product',
                    'items.orderItem',
                ]);
            });
        }
    }
