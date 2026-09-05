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
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

    class InvoiceController extends Controller {
        public function __construct() {
            $this->authorizeModel(Invoice::class, 'invoice');
        }

        public function index(InvoiceIndexRequest $request, ListInvoicesAction $action): AnonymousResourceCollection {
            return InvoiceResource::collection($action->execute($request->validated()));
        }

        public function show(Invoice $invoice, ShowInvoiceAction $action): InvoiceResource {
            return new InvoiceResource($action->execute($invoice));
        }

        public function store(Order $order, CreateInvoiceAction $action): InvoiceResource {
            $this->authorize('create', Invoice::class);

            return new InvoiceResource($action->execute($order));
        }

        public function update(UpdateInvoiceRequest $request, Invoice $invoice,
            UpdateInvoiceAction $action): InvoiceResource {
            $this->authorize('update', $invoice);

            return new InvoiceResource($action->execute($invoice, $request->validated()));
        }

        public function issue(Invoice $invoice, IssueInvoiceAction $action): InvoiceResource {
            $this->authorize('issue', $invoice);

            return new InvoiceResource($action->execute($invoice));
        }

        public function cancel(Invoice $invoice, CancelInvoiceAction $action): InvoiceResource {
            $this->authorize('cancel', $invoice);

            return new InvoiceResource($action->execute($invoice));
        }
    }
