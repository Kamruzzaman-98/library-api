<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NoticeController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/borrow-request', [BorrowController::class, 'requestBook']);
    Route::get('/my-borrows', [BorrowController::class, 'myBorrows']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/settings', [SettingController::class, 'update']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/books', [BookController::class, 'store']);
    Route::post('/books/{book}', [BookController::class, 'update']);
    Route::delete('/books/{book}', [BookController::class, 'destroy']);
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
});
