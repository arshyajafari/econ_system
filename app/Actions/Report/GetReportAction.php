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
use App\Models\CustomerTransaction;
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
        $pendingPayments = Payment::query()->where('status', PaymentStatus::PENDING)
            ->whereBetween('payment_date', [$fromDate->toDateString(), $toDate->toDateString()]);
        $recordedPayments = Payment::query()
            ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::CONFIRMED])
            ->whereBetween('payment_date', [$fromDate->toDateString(), $toDate->toDateString()]);
        $orders = Order::query()->whereBetween('ordered_at', [$fromDate, $toDate]);
        $returns = OrderReturn::query()
            ->where('status', OrderReturnStatus::COMPLETED)
            ->whereBetween('completed_at', [$fromDate, $toDate]);

        $returnCredits = CustomerTransaction::query()
            ->where('type', 'credit')
            ->whereNotNull('order_return_id')
            ->whereBetween('transaction_at', [$fromDate, $toDate]);

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

        $confirmedPaymentTotal = (float) (clone $payments)->sum('amount');
        $confirmedPaymentCount = (int) (clone $payments)->count();
        $pendingPaymentTotal = (float) (clone $pendingPayments)->sum('amount');
        $pendingPaymentCount = (int) (clone $pendingPayments)->count();

        // "ثبت‌شده" در گزارش به معنی پرداخت معتبر ثبت‌شده است:
        // تأییدشده + در انتظار تأیید. پرداخت‌های لغوشده عمداً در مبلغ/تعداد لحاظ نمی‌شوند.
        $recordedPaymentTotal = $confirmedPaymentTotal + $pendingPaymentTotal;
        $recordedPaymentCount = $confirmedPaymentCount + $pendingPaymentCount;

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
                'total' => $confirmedPaymentTotal,
                'count' => $confirmedPaymentCount,
                'pending_total' => $pendingPaymentTotal,
                'pending_count' => $pendingPaymentCount,
                'recorded_total' => $recordedPaymentTotal,
                'recorded_count' => $recordedPaymentCount,
            ],
            'orders' => [
                'total' => (int) (clone $orders)->count(),
                'completed' => (int) (clone $orders)->where('status', 'completed')->count(),
            ],
            'returns' => [
                'count' => (int) (clone $returns)->count(),
                'amount' => (float) (clone $returnCredits)->sum('amount'),
            ],
            'receivables' => [
                'debit' => (float) CustomerTransaction::query()->where('type', 'debit')->sum('amount'),
                'credit' => (float) CustomerTransaction::query()->where('type', 'credit')->sum('amount'),
                'total' => (float) CustomerTransaction::query()->where('type', 'debit')->sum('amount')
                    - (float) CustomerTransaction::query()->where('type', 'credit')->sum('amount'),
            ],
            'top_products' => $productSales,
        ];
    }
}
