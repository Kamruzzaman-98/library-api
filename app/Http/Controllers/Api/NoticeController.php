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
}
