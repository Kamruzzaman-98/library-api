<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowController extends Controller
{
    public function requestBook(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $book = Book::findOrFail($request->book_id);

        if ($book->available_copies < 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'দুঃখিত, এই বইটির কোনো কপি বর্তমানে উপলব্ধ নেই!',
            ], 400);
        }

        $existing = BorrowRecord::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->whereIn('status', ['pending', 'issued'])
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'error',
                'message' => 'আপনি ইতোমধ্যে এই বইটির জন্য আবেদন করেছেন বা বইটি আপনার কাছে রয়েছে।',
            ], 400);
        }

        $record = BorrowRecord::create([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
            'status'  => 'pending',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'বইটির জন্য আপনার আবেদন সফলভাবে জমা হয়েছে। পাঠাগার থেকে বই সংগ্রহ করুন।',
            'data'    => $record,
        ], 201);
    }
}
