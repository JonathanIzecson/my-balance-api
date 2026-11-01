<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Auth::user()->expenses()->paginate(10);

        return ExpenseResource::collection($expenses)->response()->setStatusCode(200);
    }

    public function show(Expense $expense)
    {
        return ExpenseResource::make($expense)->response()->setStatusCode(200);
    }

    public function store(ExpenseRequest $request)
    {
        $expense = Auth::user()->expenses()->create($request->validated());

        return ExpenseResource::make($expense->loadMissing('account'))->response()->setStatusCode(201);
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $expense->update($request->validated());

        return ExpenseResource::make($expense->load('account'))->response()->setStatusCode(200);
    }

    public function destroy(Expense $expense)
    {
        $expense->deleteOrFail();

        return response()->noContent();
    }
}
