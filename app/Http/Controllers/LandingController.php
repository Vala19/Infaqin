<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Donation;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $slides = Campaign::active()
            ->with(['campaigner', 'category'])
            ->orderByDesc('is_featured')
            ->orderByDesc('is_urgent')
            ->latest()
            ->take(5)
            ->get();

        $topCampaigns = Campaign::active()
            ->with(['campaigner', 'category'])
            ->orderByDesc('donor_count')
            ->take(3)
            ->get();

        $stats = [
            'raised' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'donors' => Donation::where('status', Donation::STATUS_PAID)->distinct()->count('email'),
            'campaigns' => Campaign::visible()->count(),
        ];

        // Live proof for the Open Fund Ledger and Prayer Wall feature cards (public campaigns only).
        $public = fn ($query) => $query->visible();

        $latestWithdrawal = Withdrawal::whereHas('campaign', $public)
            ->with('campaign')
            ->latest('withdrawn_at')
            ->latest('id')
            ->first();

        $latestPrayer = Comment::whereHas('campaign', $public)
            ->with(['user', 'donation', 'campaign'])
            ->latest()
            ->latest('id')
            ->first();

        $wishlistIds = $request->user()?->wishlistIds() ?? [];

        return view('landing', compact('slides', 'topCampaigns', 'stats', 'latestWithdrawal', 'latestPrayer', 'wishlistIds'));
    }
}
