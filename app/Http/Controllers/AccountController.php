<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Auth::user()->accounts()->paginate(10);

        return AccountResource::collection($accounts)->response()->setStatusCode(200);
    }

    public function show(Account $account)
    {
        return AccountResource::make($account)->response()->setStatusCode(200);
    }

    public function store(AccountRequest $request)
    {
        $account = Auth::user()->accounts()->create($request->validated());

        return AccountResource::make($account)->response()->setStatusCode(201);
    }

    public function update(AccountRequest $request, Account $account)
    {
        $account->update($request->validated());

        return AccountResource::make($account)->response()->setStatusCode(200);
    }

    public function destroy(Account $account)
    {
        $account->deleteOrFail();

        return response()->noContent();
    }
}
