<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Auth::user()->categories()->paginate(10);

        return CategoryResource::collection($categories)->response()->setStatusCode(200);
    }

    public function show(Category $category)
    {
        return CategoryResource::make($category)->response()->setStatusCode(200);
    }

    public function store(CategoryRequest $request)
    {
        $category = Auth::user()->categories()->create($request->validated());

        return CategoryResource::make($category)->response()->setStatusCode(201);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return CategoryResource::make($category)->response()->setStatusCode(200);
    }

    public function destroy(Category $category)
    {
        $category->deleteOrFail();

        return response()->noContent();
    }
}
