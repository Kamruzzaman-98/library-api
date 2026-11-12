<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Category;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $overdueRecords = BorrowRecord::with(['user', 'book'])
            ->where('status', 'issued')
            ->whereDate('due_date', '<', Carbon::today())
            ->orderBy('due_date', 'asc')
            ->get();

        $categoryStats = Category::withCount('books')
            ->having('books_count', '>', 0)
            ->get(['id', 'name', 'books_count']);

        $monthlyBorrows = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = BorrowRecord::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $monthlyBorrows[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }

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
                    'overdue_count'    => $overdueRecords->count(),
                ],
                'overdue_records'   => $overdueRecords,
                'category_stats'    => $categoryStats,
                'monthly_borrows'   => $monthlyBorrows,
                'recent_activities' => $recentActivities,
            ],
        ], 200);
    }

    public function members(Request $request)
    {
        $query = User::where('role', 'reader')->withCount('borrowRecords')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $members,
        ], 200);
    }

    public function toggleMemberStatus(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'অ্যাডমিনের স্ট্যাটাস পরিবর্তন করা যাবে না।',
            ], 400);
        }

        $user->status = $user->status === 'active' ? 'blocked' : 'active';
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => "পাঠকের স্ট্যাটাস সফলভাবে '{$user->status}' করা হয়েছে।",
            'data'    => $user,
        ], 200);
    }
}
