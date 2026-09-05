<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Payment\CancelPaymentAction;
    use App\Actions\Payment\ConfirmPaymentAction;
    use App\Actions\Payment\CreatePaymentAction;
    use App\Actions\Payment\ListPaymentsAction;
    use App\Actions\Payment\ShowPaymentAction;
    use App\Actions\Payment\UpdatePaymentAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Payment\PaymentIndexRequest;
    use App\Http\Requests\Payment\StorePaymentRequest;
    use App\Http\Requests\Payment\UpdatePaymentRequest;
    use App\Http\Resources\PaymentResource;
    use App\Models\Payment;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

    class PaymentController extends Controller {
        public function __construct() {
            $this->authorizeModel(Payment::class, 'payment');
        }

        public function index(PaymentIndexRequest $request, ListPaymentsAction $action): AnonymousResourceCollection {
            return PaymentResource::collection($action->execute($request->validated()));
        }

        public function show(Payment $payment, ShowPaymentAction $action): PaymentResource {
            return new PaymentResource($action->execute($payment));
        }

        public function store(StorePaymentRequest $request, CreatePaymentAction $action): PaymentResource {
            $this->authorize('create', Payment::class);

            return new PaymentResource($action->execute($request->validated(), $request->user()));
        }

        public function update(UpdatePaymentRequest $request, Payment $payment,
            UpdatePaymentAction $action): PaymentResource {
            $this->authorize('update', $payment);

            return new PaymentResource($action->execute($payment, $request->validated()));
        }

        public function confirm(Payment $payment, ConfirmPaymentAction $action): PaymentResource {
            $this->authorize('confirm', $payment);

            return new PaymentResource($action->execute($payment));
        }

        public function cancel(Payment $payment, CancelPaymentAction $action): PaymentResource {
            $this->authorize('cancel', $payment);

            return new PaymentResource($action->execute($payment));
        }
    }
