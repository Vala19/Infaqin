<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
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

        return view('landing', compact('slides', 'topCampaigns', 'stats'));
    }
}
