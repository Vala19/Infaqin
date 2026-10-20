<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Donation;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $paid = $user->donations()->where('status', Donation::STATUS_PAID);

        $stats = [
            'total' => (int) (clone $paid)->sum('amount'),
            'campaigns' => (clone $paid)->distinct()->count('campaign_id'),
            'streak' => $user->kindnessStreak(),
        ];

        $topCampaigns = Campaign::active()
            ->with(['campaigner', 'category'])
            ->orderByDesc('donor_count')
            ->take(3)
            ->get();

        $urgentCampaign = Campaign::active()
            ->with(['campaigner', 'category'])
            ->where('is_urgent', true)
            ->whereNotIn('id', $topCampaigns->pluck('id'))
            ->orderBy('deadline')
            ->first() ?? $topCampaigns->first();

        $prayers = Comment::with(['user', 'donation', 'campaign'])
            ->latest()
            ->take(6)
            ->get();

        $amenedIds = $user->amenedComments()
            ->whereIn('comments.id', $prayers->pluck('id'))
            ->pluck('comments.id')
            ->all();

        $news = News::published()->latest('published_at')->take(3)->get();

        return view('home', compact('stats', 'topCampaigns', 'urgentCampaign', 'prayers', 'amenedIds', 'news'));
    }
}
