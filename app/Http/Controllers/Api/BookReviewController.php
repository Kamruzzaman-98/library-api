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
}
