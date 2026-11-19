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
            'phone'             => 'required|string|max:25',
            'address'           => 'nullable|string|max:255',
            'donation_type'     => 'required|in:book,money',
            'amount_or_details' => 'required|string|max:255',
            'message'           => 'nullable|string|max:500',
        ], [
            'donor_name.required'        => 'আপনার নাম আবশ্যক।',
            'phone.required'             => 'মোবাইল নম্বর আবশ্যক।',
            'amount_or_details.required' => 'অনুদানের পরিমাণ বা বইয়ের বিবরণ লিখুন।',
        ]);

        $validated['status'] = 'pending';
        $donation = Donation::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'আপনার অনুদানের আবেদন সফলভাবে জমা হয়েছে! অ্যাডমিন অনুমোদনের পর তা Donors Wall-এ প্রকাশিত হবে।',
            'data'    => $donation,
        ], 201);
    }

    public function adminDonations()
    {
        $donations = Donation::latest()->get();
        return response()->json([
            'status' => 'success',
            'data'   => $donations,
        ], 200);
    }

    public function storeDonation(Request $request)
    {
        $validated = $request->validate([
            'donor_name'        => 'required|string|max:255',
            'phone'             => 'nullable|string|max:25',
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

    public function updateDonation(Request $request, Donation $donation)
    {
        $validated = $request->validate([
            'donor_name'        => 'required|string|max:255',
            'phone'             => 'nullable|string|max:25',
            'address'           => 'nullable|string|max:255',
            'donation_type'     => 'required|in:book,money',
            'amount_or_details' => 'required|string|max:255',
            'message'           => 'nullable|string|max:500',
            'status'            => 'required|in:pending,approved,rejected',
        ]);

        $donation->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'অনুদানের তথ্য সফলভাবে আপডেট হয়েছে!',
            'data'    => $donation,
        ], 200);
    }

    public function approveDonation(Donation $donation)
    {
        $donation->update(['status' => 'approved']);

        return response()->json([
            'status'  => 'success',
            'message' => 'অনুদানটি অনুমোদিত হয়েছে এবং হোমপেজে প্রদর্শিত হবে!',
        ], 200);
    }

    public function rejectDonation(Donation $donation)
    {
        $donation->update(['status' => 'rejected']);

        return response()->json([
            'status'  => 'success',
            'message' => 'অনুদান আবেদনটি বাতিল করা হয়েছে।',
        ], 200);
    }

    public function destroyDonation(Donation $donation)
    {
        $donation->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'অনুদান রেকর্ড মুছে ফেলা হয়েছে!',
        ], 200);
    }

    public function storeCommittee(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'designation'   => 'required|string|max:255',
            'phone'         => 'nullable|string|max:25',
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

    public function updateCommittee(Request $request, CommitteeMember $committeeMember)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'designation'   => 'required|string|max:255',
            'phone'         => 'nullable|string|max:25',
            'session_year'  => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:1',
            'photo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($committeeMember->photo) {
                $oldPath = str_replace('/storage/', '', $committeeMember->photo);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('photo')->store('committee', 'public');
            $validated['photo'] = Storage::url($path);
        }

        $committeeMember->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'সদস্যের তথ্য সফলভাবে আপডেট হয়েছে!',
            'data'    => $committeeMember,
        ], 200);
    }

    public function destroyCommittee(CommitteeMember $committeeMember)
    {
        if ($committeeMember->photo) {
            $oldPath = str_replace('/storage/', '', $committeeMember->photo);
            Storage::disk('public')->delete($oldPath);
        }
        $committeeMember->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'সদস্যের তথ্য মুছে ফেলা হয়েছে!',
        ], 200);
    }
}
