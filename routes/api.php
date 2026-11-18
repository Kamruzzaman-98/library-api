<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ১. পাবলিক রুটস (লগইন ছাড়া সবাই ভিজিট ও ব্যবহার করতে পারবে)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// পাঠাগারের সাধারণ তথ্য, বই ও ক্যাটাগরি
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);

// পাবলিক নোটিশ, দাতা তালিকা ও কমিটি
Route::get('/notices', [NoticeController::class, 'index']);
Route::get('/community', [CommunityController::class, 'publicData']);

// পাবলিক ও রিডার অনুদান আবেদন (লগইন ছাড়া ও লগইন সহ সবাই সাবমিট করতে পারবে)
Route::post('/donations', [CommunityController::class, 'submitDonation']);

/*
|--------------------------------------------------------------------------
| ২. অথেন্টিকেটেড ইউজার রুটস (লগইন করা রিডার ও অ্যাডমিন উভয়ের জন্য)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // পাঠকের বই আবেদন ও নিজের হিস্ট্রি
    Route::post('/borrow-request', [BorrowController::class, 'requestBook']);
    Route::get('/my-borrows', [BorrowController::class, 'myBorrows']);

    // পাঠকের বই রিজার্ভেশন / ওয়েটিং লিস্ট
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/my-reservations', [ReservationController::class, 'myReservations']);
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
});

/*
|--------------------------------------------------------------------------
| ৩. অ্যাডমিন প্রটেক্টেড রুটস (সব রুটের শুরুতে স্বয়ংক্রিয়ভাবে /api/admin/ বসবে)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // ড্যাশবোর্ড অ্যানালিটিক্স ও মেম্বার ম্যানেজমেন্ট
    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/members', [DashboardController::class, 'members']);
    Route::post('/members/{user}/toggle-status', [DashboardController::class, 'toggleMemberStatus']);

    // পাঠাগার সেটিংস আপডেট
    Route::post('/settings', [SettingController::class, 'update']);

    // ক্যাটাগরি CRUD
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // বই CRUD (ছবি আপলোড সাপোর্টের জন্য POST দিয়ে আপডেট)
    Route::post('/books', [BookController::class, 'store']);
    Route::post('/books/{book}', [BookController::class, 'update']);
    Route::delete('/books/{book}', [BookController::class, 'destroy']);

    // বই ইস্যু, রিটার্ন ও রিকোয়েস্ট ম্যানেজমেন্ট
    Route::get('/borrows', [BorrowController::class, 'adminBorrows']);
    Route::post('/borrows/{borrowRecord}/issue', [BorrowController::class, 'issueBook']);
    Route::post('/borrows/{borrowRecord}/return', [BorrowController::class, 'returnBook']);
    Route::post('/borrows/{borrowRecord}/reject', [BorrowController::class, 'rejectRequest']);

    // অ্যাডমিন ওয়েটিং লিস্ট (Reservations)
    Route::get('/reservations', [ReservationController::class, 'adminIndex']);
    Route::post('/reservations/{reservation}/issue', [ReservationController::class, 'fulfillAndIssue']);

    // নোটিশ বোর্ড ম্যানেজমেন্ট
    Route::get('/notices', [NoticeController::class, 'adminIndex']);
    Route::post('/notices', [NoticeController::class, 'store']);
    Route::delete('/notices/{notice}', [NoticeController::class, 'destroy']);

    // অনুদান (Donors) ও পরিচালনা পর্ষদ (Committee) ম্যানেজমেন্ট
    Route::get('/donations', [CommunityController::class, 'adminDonations']);
    Route::post('/donations', [CommunityController::class, 'storeDonation']);
    Route::post('/donations/{donation}/approve', [CommunityController::class, 'approveDonation']);
    Route::delete('/donations/{donation}', [CommunityController::class, 'destroyDonation']);

    Route::post('/committee', [CommunityController::class, 'storeCommittee']);
    Route::post('/committee/{committeeMember}', [CommunityController::class, 'updateCommittee']);
    Route::delete('/committee/{committeeMember}', [CommunityController::class, 'destroyCommittee']);
});
