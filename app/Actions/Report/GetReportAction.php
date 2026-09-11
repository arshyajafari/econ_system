<?php

namespace App\Actions\Report;

use App\Enums\InvoiceStatus;
use App\Enums\OrderReturnStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class GetReportAction
{
    public function execute(string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $issued = Invoice::query()->where('status', InvoiceStatus::ISSUED)
            ->whereBetween('issued_at', [$fromDate, $toDate]);
        $payments = Payment::query()->where('status', PaymentStatus::CONFIRMED)
            ->whereBetween('payment_date', [$fromDate->toDateString(), $toDate->toDateString()]);
        $orders = Order::query()->whereBetween('ordered_at', [$fromDate, $toDate]);
        $returns = OrderReturn::query()->where('status', OrderReturnStatus::CONFIRMED)
            ->whereBetween('completed_at', [$fromDate, $toDate]);

        $productSales = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.status', InvoiceStatus::ISSUED)
            ->whereBetween('invoices.issued_at', [$fromDate, $toDate])
            ->selectRaw('invoice_items.product_id, products.public_id as id, products.code, products.title, SUM(invoice_items.quantity) as quantity, SUM(invoice_items.total_price) as total_amount')
            ->groupBy('invoice_items.product_id', 'products.public_id', 'products.code', 'products.title')
            ->orderByDesc('total_amount')->limit(10)->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'title' => $item->title,
                'quantity' => (int) $item->quantity,
                'total_amount' => (float) $item->total_amount,
            ])->values()->all();

        return [
            'period' => ['from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()],
            'sales' => [
                'subtotal' => (float) (clone $issued)->sum('subtotal'),
                'discount' => (float) (clone $issued)->sum('discount_amount'),
                'tax' => (float) (clone $issued)->sum('tax_amount'),
                'total' => (float) (clone $issued)->sum('total_amount'),
                'invoice_count' => (int) (clone $issued)->count(),
            ],
            'payments' => [
                'total' => (float) (clone $payments)->sum('amount'),
                'count' => (int) (clone $payments)->count(),
            ],
            'orders' => [
                'total' => (int) (clone $orders)->count(),
                'completed' => (int) (clone $orders)->where('status', 'completed')->count(),
            ],
            'returns' => [
                'count' => (int) (clone $returns)->count(),
                'amount' => (float) (clone $returns)->with('items')->get()->sum(fn ($return) => $return->items->sum('total_price')),
            ],
            'receivables' => [
                'total' => (float) Invoice::query()->where('status', InvoiceStatus::ISSUED)
                    ->sum('total_amount') - (float) Payment::query()->where('status', PaymentStatus::CONFIRMED)->sum('amount'),
            ],
            'top_products' => $productSales,
        ];
    }
}
