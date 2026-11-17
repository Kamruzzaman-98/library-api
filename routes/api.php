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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/settings', [SettingController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);
Route::get('/notices', [NoticeController::class, 'index']);

Route::get('/community', [CommunityController::class, 'publicData']);
Route::post('/donations', [CommunityController::class, 'submitDonation']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/borrow-request', [BorrowController::class, 'requestBook']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/my-reservations', [ReservationController::class, 'myReservations']);
    Route::post('/reservations/cancel/{reservation}', [ReservationController::class, 'cancel']);
    Route::get('/my-borrows', [BorrowController::class, 'myBorrows']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/settings', [SettingController::class, 'update']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::post('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/books', [BookController::class, 'store']);
    Route::post('/books/{book}', [BookController::class, 'update']);
    Route::delete('/books/{book}', [BookController::class, 'destroy']);
    Route::get('/reservations', [ReservationController::class, 'adminIndex']);
    Route::post('/reservations/issue/{reservation}', [ReservationController::class, 'fulfillAndIssue']);
    Route::get('/borrows', [BorrowController::class, 'adminBorrows']);
    Route::post('/borrows/issue/{borrowRecord}', [BorrowController::class, 'issueBook']);
    Route::post('/borrows/return/{borrowRecord}', [BorrowController::class, 'returnBook']);
    Route::post('/borrows/reject/{borrowRecord}', [BorrowController::class, 'rejectRequest']);
    Route::get('/manage/notices', [NoticeController::class, 'adminIndex']);
    Route::post('/notices', [NoticeController::class, 'store']);
    Route::delete('/notices/{notice}', [NoticeController::class, 'destroy']);

    Route::get('/admin/stats', [DashboardController::class, 'stats']);
    Route::get('/admin/members', [DashboardController::class, 'members']);
    Route::post('/admin/members/toggle-status/{user}', [DashboardController::class, 'toggleMemberStatus']);

    Route::get('/donations', [CommunityController::class, 'adminDonations']);
    Route::post('/donations', [CommunityController::class, 'storeDonation']);
    Route::post('/donations/approve/{donation}', [CommunityController::class, 'approveDonation']);
    Route::delete('/donations/{donation}', [CommunityController::class, 'destroyDonation']);

    Route::post('/committee', [CommunityController::class, 'storeCommittee']);
    Route::post('/committee/{committeeMember}', [CommunityController::class, 'updateCommittee']);
    Route::delete('/committee/{committeeMember}', [CommunityController::class, 'destroyCommittee']);
});
