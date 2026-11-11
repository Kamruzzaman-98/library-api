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

    public function myBorrows(Request $request)
    {
        $records = BorrowRecord::with('book')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $records,
        ], 200);
    }

    public function adminBorrows(Request $request)
    {
        $query = BorrowRecord::with(['user', 'book'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $records,
        ], 200);
    }

    public function issueBook(Request $request, BorrowRecord $borrowRecord)
    {
        if ($borrowRecord->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'শুধুমাত্র পেন্ডিং রিকোয়েস্ট ইস্যু করা সম্ভব।',
            ], 400);
        }

        $request->validate([
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        $days = $request->days ?? 14;

        DB::transaction(function () use ($borrowRecord, $days) {
            $book = Book::lockForUpdate()->find($borrowRecord->book_id);

            if ($book->available_copies < 1) {
                throw new \Exception('বইটির পর্যাপ্ত কপি অবশিষ্ট নেই!');
            }

            $book->decrement('available_copies');

            $borrowRecord->update([
                'status'     => 'issued',
                'issue_date' => Carbon::now(),
                'due_date'   => Carbon::now()->addDays($days),
            ]);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'বইটি সফলভাবে ইস্যু করা হয়েছে!',
        ], 200);
    }

    public function returnBook(BorrowRecord $borrowRecord)
    {
        if ($borrowRecord->status !== 'issued') {
            return response()->json([
                'status'  => 'error',
                'message' => 'এই বইটি বর্তমানে ইস্যুকৃত অবস্থায় নেই।',
            ], 400);
        }

        DB::transaction(function () use ($borrowRecord) {
            Book::where('id', $borrowRecord->book_id)->increment('available_copies');

            $borrowRecord->update([
                'status'      => 'returned',
                'return_date' => Carbon::now(),
            ]);
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'বইটি সফলভাবে ফেরত গ্রহণ করা হয়েছে!',
        ], 200);
    }

    public function rejectRequest(BorrowRecord $borrowRecord)
    {
        if ($borrowRecord->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'শুধুমাত্র পেন্ডিং আবেদন বাতিল করা যাবে।',
            ], 400);
        }

        $borrowRecord->update(['status' => 'rejected']);

        return response()->json([
            'status'  => 'success',
            'message' => 'বইয়ের আবেদনটি বাতিল করা হয়েছে।',
        ], 200);
    }
}
