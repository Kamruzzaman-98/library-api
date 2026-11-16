<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function myReservations(Request $request)
    {
        $list = Reservation::with('book')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ], 200);
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'অনুমতি নেই।'], 403);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json([
            'status'  => 'success',
            'message' => 'রিজার্ভেশন বাতিল করা হয়েছে।',
        ], 200);
    }

    public function adminIndex()
    {
        $reservations = Reservation::with(['user', 'book'])
            ->whereIn('status', ['waiting', 'notified'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $reservations,
        ], 200);
    }

    public function fulfillAndIssue(Request $request, Reservation $reservation)
    {
        $book = Book::findOrFail($reservation->book_id);

        if ($book->available_copies < 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'বইটির কোনো কপি বর্তমানে শেলফে মজুদ নেই!',
            ], 400);
        }

        DB::transaction(function () use ($reservation, $book) {
            $book->decrement('available_copies');

            BorrowRecord::create([
                'user_id'    => $reservation->user_id,
                'book_id'    => $reservation->book_id,
                'issue_date' => now(),
                'due_date'   => now()->addDays(14),
                'status'     => 'issued',
            ]);

            $reservation->update(['status' => 'fulfilled']);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'রিজার্ভেশন সম্পন্ন করে পাঠকের নামে বইটি ইস্যু করা হয়েছে!',
        ], 200);
    }
}
