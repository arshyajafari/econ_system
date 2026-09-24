<?php

namespace App\Http\Controllers\Api;

use App\Actions\Expense\CreateExpenseAction;
use App\Actions\Expense\DeleteExpenseAction;
use App\Actions\Expense\ListExpensesAction;
use App\Actions\Expense\UpdateExpenseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\ExpenseIndexRequest;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeModel(Expense::class, 'expense');
    }

    public function index(
        ExpenseIndexRequest $request,
        ListExpensesAction $action
    ): AnonymousResourceCollection {
        return ExpenseResource::collection(
            $action->execute($request->validated())
        );
    }

    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense->load(Expense::DEFAULT_RELATIONS));
    }

    public function store(
        StoreExpenseRequest $request,
        CreateExpenseAction $action
    ): JsonResponse {
        return response()->json(
            new ExpenseResource($action->execute($request->validated(), $request->user())),
            201
        );
    }

    public function update(
        UpdateExpenseRequest $request,
        Expense $expense,
        UpdateExpenseAction $action
    ): ExpenseResource {
        return new ExpenseResource(
            $action->execute($expense, $request->validated())
        );
    }

    public function destroy(
        Expense $expense,
        DeleteExpenseAction $action
    ): JsonResponse {
        $action->execute($expense);

        return response()->json(['message' => 'هزینه با موفقیت حذف شد.']);
    }
}
