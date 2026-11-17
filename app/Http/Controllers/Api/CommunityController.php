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

    public function storeDonation(Request $request)
    {
        $validated = $request->validate([
            'donor_name'        => 'required|string|max:255',
            'phone'             => 'nullable|string|max:20',
            'address'           => 'nullable|string|max:255',
            'donation_type'     => 'required|in:book,money',
            'amount_or_details' => 'required|string|max:255',
            'message'           => 'nullable|string|max:500',
        ]);

        $validated['status'] = 'approved';
        $donation = Donation::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'দাতা সদস্যের তথ্য Donors Wall-এ যুক্ত হয়েছে!',
            'data'    => $donation,
        ], 201);
    }

    public function approveDonation(Donation $donation)
    {
        $donation->update(['status' => 'approved']);
        return response()->json([
            'status'  => 'success',
            'message' => 'অনুদানটি অনুমোদিত হয়েছে এবং হোমপেজে প্রদর্শিত হবে!',
        ], 200);
    }

    public function destroyDonation(Donation $donation)
    {
        $donation->delete();
        return response()->json(['status' => 'success', 'message' => 'অনুদান রেকর্ড মুছে ফেলা হয়েছে!'], 200);
    }

    public function storeCommittee(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'designation'   => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'session_year'  => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:1',
            'photo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('committee', 'public');
            $validated['photo'] = Storage::url($path);
        }

        $member = CommitteeMember::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'পরিচালনা পর্ষদের সদস্য যুক্ত করা হয়েছে!',
            'data'    => $member,
        ], 201);
    }

    public function destroyCommittee(CommitteeMember $committeeMember)
    {
        if ($committeeMember->photo) {
            $oldPath = str_replace('/storage/', '', $committeeMember->photo);
            Storage::disk('public')->delete($oldPath);
        }
        $committeeMember->delete();

        return response()->json(['status' => 'success', 'message' => 'সদস্যের তথ্য মুছে ফেলা হয়েছে!'], 200);
    }
}
