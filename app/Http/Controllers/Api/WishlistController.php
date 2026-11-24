<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlists = Wishlist::with('book.category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $wishlists,
        ], 200);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('book_id', $request->book_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'status'  => 'success',
                'added'   => false,
                'message' => 'বইটি পছন্দের তালিকা থেকে সরানো হয়েছে।',
            ], 200);
        }

        Wishlist::create([
            'user_id' => $request->user()->id,
            'book_id' => $request->book_id,
        ]);

        return response()->json([
            'status'  => 'success',
            'added'   => true,
            'message' => 'বইটি পছন্দের তালিকায় (Wishlist) যুক্ত হয়েছে!',
        ], 201);
    }
}
