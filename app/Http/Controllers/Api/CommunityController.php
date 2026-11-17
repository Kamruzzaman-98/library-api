<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommitteeMember;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommunityController extends Controller
{
    public function publicData()
    {
        $donors = Donation::where('status', 'approved')
            ->latest()
            ->get();

        $committee = CommitteeMember::orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'donors'    => $donors,
                'committee' => $committee,
            ],
        ], 200);
    }
}
