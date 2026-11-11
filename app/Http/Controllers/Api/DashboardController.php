<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Category;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalBooksTitles = Book::count();
        $totalBookCopies = Book::sum('total_copies');
        $availableCopies = Book::sum('available_copies');
        $totalReaders = User::where('role', 'reader')->count();

        $pendingRequests = BorrowRecord::where('status', 'pending')->count();
        $currentlyIssued = BorrowRecord::where('status', 'issued')->count();
        $totalReturned = BorrowRecord::where('status', 'returned')->count();

        $recentActivities = BorrowRecord::with(['user', 'book'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'counts' => [
                    'book_titles'      => $totalBooksTitles,
                    'total_copies'     => $totalBookCopies,
                    'available_copies' => $availableCopies,
                    'borrowed_copies'  => $totalBookCopies - $availableCopies,
                    'total_readers'    => $totalReaders,
                    'pending_requests' => $pendingRequests,
                    'currently_issued' => $currentlyIssued,
                    'total_returned'   => $totalReturned,
                ],
                'recent_activities' => $recentActivities,
            ],
        ], 200);
    }
}
