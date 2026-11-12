<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'issue_date',
        'due_date',
        'return_date',
        'status',
        'admin_note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    protected $appends = ['overdue_days', 'fine_amount'];

    public function getOverdueDaysAttribute(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        $dueDate = Carbon::parse($this->due_date)->startOfDay();

        $compareDate = ($this->status === 'returned' && $this->return_date)
            ? Carbon::parse($this->return_date)->startOfDay()
            : Carbon::today();

        if ($this->status === 'issued' && $compareDate->gt($dueDate)) {
            return $dueDate->diffInDays($compareDate);
        }

        if ($this->status === 'returned' && $compareDate->gt($dueDate)) {
            return $dueDate->diffInDays($compareDate);
        }

        return 0;
    }

    public function getFineAmountAttribute(): int
    {
        $dailyFineRate = 1;
        return $this->overdue_days * $dailyFineRate;
    }
}
