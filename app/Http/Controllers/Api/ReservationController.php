<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $book = Book::findOrFail($request->book_id);

        if ($book->available_copies > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'বইটির কপি বর্তমানে শেলফে মজুদ আছে! আপনি সরাসরি পড়ার আবেদন করতে পারেন।',
            ], 400);
        }

        $alreadyReserved = Reservation::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->exists();

        if ($alreadyReserved) {
            return response()->json([
                'status'  => 'error',
                'message' => 'আপনি ইতোমধ্যে এই বইটির ওয়েটিং লিস্টে যুক্ত আছেন।',
            ], 400);
        }

        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
            'status'  => 'waiting',
        ]);

        $queuePosition = Reservation::where('book_id', $book->id)
            ->where('status', 'waiting')
            ->where('id', '<=', $reservation->id)
            ->count();

        return response()->json([
            'status'  => 'success',
            'message' => "বইটি রিজার্ভ করা হয়েছে! ওয়েটিং লিস্টে আপনার সিরিয়াল নম্বর: #{$queuePosition}",
            'data'    => $reservation,
        ], 201);
    }
}
