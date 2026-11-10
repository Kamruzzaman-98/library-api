<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('books')->latest()->get();
        return response()->json(['status' => 'success', 'data' => $categories], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Category added successfully!',
            'data'    => $category,
        ], 201);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json([
            'status'  => 'success',
            'message' => 'Category deleted successfully!',
        ], 200);
    }
}
