<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReview;
use App\Models\BorrowRecord;
use Illuminate\Http\Request;

class BookReviewController extends Controller
{
    public function index(Book $book)
    {
        $reviews = BookReview::with('user:id,name')
            ->where('book_id', $book->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'average_rating' => round($book->reviews()->avg('rating') ?? 0, 1),
                'total_reviews'  => $reviews->count(),
                'reviews'        => $reviews,
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $hasRead = BorrowRecord::where('user_id', $request->user()->id)
            ->where('book_id', $validated['book_id'])
            ->where('status', 'returned')
            ->exists();

        if (!$hasRead) {
            return response()->json([
                'status'  => 'error',
                'message' => 'শুধুমাত্র বইটি পড়া শেষে ফেরত দিলেই আপনি রিভিউ দিতে পারবেন।',
            ], 403);
        }

        $review = BookReview::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $validated['book_id'],
            ],
            [
                'rating'  => $validated['rating'],
                'comment' => $validated['comment'],
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'আপনার মূল্যবান রিভিউ ও রেটিং সফলভাবে সংরক্ষিত হয়েছে!',
            'data'    => $review,
        ], 201);
    }
}
