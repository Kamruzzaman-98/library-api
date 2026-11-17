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

    public function submitDonation(Request $request)
    {
        $validated = $request->validate([
            'donor_name'        => 'required|string|max:255',
            'phone'             => 'required|string|max:20',
            'address'           => 'nullable|string|max:255',
            'donation_type'     => 'required|in:book,money',
            'amount_or_details' => 'required|string|max:255',
            'message'           => 'nullable|string|max:500',
        ]);

        $validated['status'] = 'pending';
        $donation = Donation::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'আপনার অনুদানের আগ্রহ সফলভাবে জমা হয়েছে! পাঠাগার কর্তৃপক্ষ শীঘ্রই যোগাযোগ করবে।',
            'data'    => $donation,
        ], 201);
    }

    public function adminDonations()
    {
        $donations = Donation::latest()->get();
        return response()->json(['status' => 'success', 'data' => $donations], 200);
    }
}
