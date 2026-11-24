<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRecord;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function exportBooks()
    {
        $books = Book::with('category')->orderBy('title', 'asc')->get();
        $fileName = 'library_books_report_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($books) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ক্রমিক নং',
                'বইয়ের শিরোনাম',
                'লেখক',
                'ক্যাটাগরি',
                'প্রকাশক',
                'ISBN',
                'শেলফ নং',
                'মোট কপি',
                'শেলফে মজুদ',
                'পাঠকের কাছে',
            ]);

            foreach ($books as $index => $book) {
                fputcsv($file, [
                    $index + 1,
                    $book->title,
                    $book->author,
                    $book->category?->name ?? 'N/A',
                    $book->publisher ?? '---',
                    $book->isbn ?? '---',
                    $book->shelf_no ?? 'N/A',
                    $book->total_copies,
                    $book->available_copies,
                    $book->total_copies - $book->available_copies,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportBorrows(Request $request)
    {
        $query = BorrowRecord::with(['user', 'book'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->get();
        $fileName = 'library_circulation_report_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'রেকর্ড ID',
                'মেম্বার ID',
                'পাঠকের নাম',
                'মোবাইল নম্বর',
                'বইয়ের নাম',
                'ইস্যুর তারিখ',
                'ফেরতের শেষ তারিখ',
                'ফেরত প্রদানের তারিখ',
                'বিলম্ব (দিন)',
                'জরিমানা (টাকা)',
                'অবস্থা',
            ]);

            foreach ($records as $rec) {
                fputcsv($file, [
                    $rec->id,
                    $rec->user?->member_code ?? 'N/A',
                    $rec->user?->name ?? 'N/A',
                    $rec->user?->phone ?? 'N/A',
                    $rec->book?->title ?? 'N/A',
                    $rec->issue_date ?? '---',
                    $rec->due_date ?? '---',
                    $rec->return_date ?? '---',
                    $rec->overdue_days,
                    $rec->fine_amount,
                    strtoupper($rec->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
