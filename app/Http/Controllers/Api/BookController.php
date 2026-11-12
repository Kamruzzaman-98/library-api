<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with('category')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->boolean('available_only')) {
            $query->where('available_copies', '>', 0);
        }

        $books = $query->paginate(12);

        return response()->json([
            'status' => 'success',
            'data'   => $books,
        ], 200);
    }

    public function show(Book $book)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $book->load('category'),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'title'        => 'required|string|max:255',
            'author'       => 'required|string|max:255',
            'publisher'    => 'nullable|string|max:255',
            'isbn'         => 'nullable|string|unique:books,isbn',
            'total_copies' => 'required|integer|min:1',
            'shelf_no'     => 'nullable|string|max:50',
            'description'  => 'nullable|string',
            'cover_image'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $validated['available_copies'] = $validated['total_copies'];

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('books', 'public');
            $validated['cover_image'] = Storage::url($path);
        }

        $book = Book::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Book saved successfully!',
            'data'    => $book,
        ], 201);
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'title'        => 'required|string|max:255',
            'author'       => 'required|string|max:255',
            'publisher'    => 'nullable|string|max:255',
            'isbn'         => 'nullable|string|unique:books,isbn,' . $book->id,
            'total_copies' => 'required|integer|min:1',
            'shelf_no'     => 'nullable|string|max:50',
            'description'  => 'nullable|string',
            'cover_image'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $copyDifference = $validated['total_copies'] - $book->total_copies;
        $newAvailable = max(0, $book->available_copies + $copyDifference);
        $validated['available_copies'] = $newAvailable;

        if ($request->hasFile('cover_image')) {
            if ($book->cover_image) {
                $oldPath = str_replace('/storage/', '', $book->cover_image);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('cover_image')->store('books', 'public');
            $validated['cover_image'] = Storage::url($path);
        }

        $book->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'বইয়ের তথ্য সফলভাবে আপডেট হয়েছে!',
            'data'    => $book->load('category'),
        ], 200);
    }

    public function destroy(Book $book)
    {
        if ($book->cover_image) {
            $filePath = str_replace('/storage/', '', $book->cover_image);
            Storage::disk('public')->delete($filePath);
        }

        $book->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Book deleted successfully!',
        ], 200);
    }
}
