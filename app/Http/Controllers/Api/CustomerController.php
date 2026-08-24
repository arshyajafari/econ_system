<?php

    namespace App\Http\Controllers\Api;

    use App\Actions\Customer\ChangeCustomerStatusAction;
    use App\Actions\Customer\CreateCustomerAction;
    use App\Actions\Customer\DeleteCustomerAction;
    use App\Actions\Customer\ListCustomersAction;
    use App\Actions\Customer\RestoreCustomerAction;
    use App\Actions\Customer\ShowCustomerAction;
    use App\Actions\Customer\UpdateCustomerAction;
    use App\Http\Controllers\Controller;
    use App\Http\Requests\Customer\ChangeCustomerStatusRequest;
    use App\Http\Requests\Customer\CustomerIndexRequest;
    use App\Http\Requests\Customer\StoreCustomerRequest;
    use App\Http\Requests\Customer\UpdateCustomerRequest;
    use App\Http\Resources\Customer\CustomerCollection;
    use App\Http\Resources\CustomerResource;
    use App\Models\Customer;
    use Illuminate\Http\JsonResponse;
    use Symfony\Component\HttpFoundation\Response;

    class CustomerController extends Controller {
        public function __construct() {
            $this->authorizeModel(Customer::class, 'customer');
        }

        public function index(CustomerIndexRequest $request, ListCustomersAction $action): CustomerCollection {
            return new CustomerCollection($action->execute($request->validated()));
        }

        public function store(StoreCustomerRequest $request, CreateCustomerAction $action): CustomerResource {
            return CustomerResource::make($action->execute($request->validated()));
        }

        public function show(Customer $customer, ShowCustomerAction $action): CustomerResource {
            return CustomerResource::make($action->execute($customer));
        }

        public function update(UpdateCustomerRequest $request, Customer $customer,
            UpdateCustomerAction $action): CustomerResource {

            return CustomerResource::make($action->execute($customer, $request->validated()));
        }

        public function destroy(Customer $customer, DeleteCustomerAction $action): JsonResponse {
            $action->execute($customer);

            return response()->json([], Response::HTTP_NO_CONTENT);
        }

        public function restore(Customer $customer, RestoreCustomerAction $action): CustomerResource {
            return CustomerResource::make($action->execute($customer));
        }

        public function changeStatus(ChangeCustomerStatusRequest $request, Customer $customer,
            ChangeCustomerStatusAction $action): CustomerResource {

            return CustomerResource::make($action->execute($customer, $request->status()));
        }
    }
