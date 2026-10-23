<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeRequest;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    public function index()
    {
        $incomes = Auth::user()->incomes()->paginate(10);

        return IncomeResource::collection($incomes)->response()->setStatusCode(200);
    }

    public function show(Income $income)
    {
        return IncomeResource::make($income)->response()->setStatusCode(200);
    }

    public function store(IncomeRequest $request)
    {
        $income = Auth::user()->incomes()->create($request->validated());

        return IncomeResource::make($income->loadMissing('account'))->response()->setStatusCode(201);
    }

    public function update(IncomeRequest $request, Income $income)
    {
        $income->update($request->validated());

        return IncomeResource::make($income)->response()->setStatusCode(200);
    }

    public function destroy(Income $income)
    {
        $income->deleteOrFail();

        return response()->noContent();
    }
}
