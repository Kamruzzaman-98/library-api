<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index()
    {
        $notices = Notice::where('is_active', true)
            ->latest('published_at')
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $notices,
        ], 200);
    }

    public function adminIndex()
    {
        $notices = Notice::latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data'   => $notices,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'published_at' => 'nullable|date',
            'is_active'    => 'boolean',
        ]);

        $validated['published_at'] = $validated['published_at'] ?? Carbon::today();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $notice = Notice::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'নোটিশ সফলভাবে প্রকাশিত হয়েছে!',
            'data'    => $notice,
        ], 201);
    }
}
