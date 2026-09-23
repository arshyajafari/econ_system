<?php

namespace App\Http\Controllers\Api;

use App\Actions\Invoice\CancelInvoiceAction;
use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\IssueInvoiceAction;
use App\Actions\Invoice\ListInvoicesAction;
use App\Actions\Invoice\ShowInvoiceAction;
use App\Actions\Invoice\UpdateInvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\InvoiceIndexRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct() {}

    public function index(InvoiceIndexRequest $request, ListInvoicesAction $action): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);
        return InvoiceResource::collection($action->execute($request->validated(), $request->user()));
    }

    /**
     * Returns only invoices that the payment form is allowed to use.
     * Settlement operators need this narrow read path without receiving
     * general invoice access.
     */
    public function payableForPayment(InvoiceIndexRequest $request, ListInvoicesAction $action): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        $filters = array_merge($request->validated(), [
            'status' => 'issued',
            'settled' => false,
            'payable' => true,
        ]);

        return InvoiceResource::collection($action->execute($filters, $request->user()));
    }

    public function show(Invoice $invoice, ShowInvoiceAction $action): InvoiceResource
    {
        $this->authorize('view', $invoice);
        return new InvoiceResource($action->execute($invoice));
    }

    public function store(Order $order, CreateInvoiceAction $action): InvoiceResource
    {
        $this->authorize('create', Invoice::class);
        return new InvoiceResource($action->execute($order));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, UpdateInvoiceAction $action): InvoiceResource
    {
        $this->authorize('update', $invoice);
        return new InvoiceResource($action->execute($invoice, $request->validated()));
    }

    public function issue(Invoice $invoice, IssueInvoiceAction $action): InvoiceResource
    {
        $this->authorize('issue', $invoice);
        return new InvoiceResource($action->execute($invoice));
    }

    public function cancel(Invoice $invoice, CancelInvoiceAction $action): InvoiceResource
    {
        $this->authorize('cancel', $invoice);
        return new InvoiceResource($action->execute($invoice));
    }
}
