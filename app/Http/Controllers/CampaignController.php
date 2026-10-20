<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public const SORTS = [
        'popular' => 'Most donors',
        'newest' => 'Newest',
        'ending' => 'Ending soon',
        'most_funded' => 'Most funded',
        'least_funded' => 'Needs help most',
        'target_high' => 'Biggest target',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'array'],
            'category.*' => [Rule::in(array_keys(Campaign::CATEGORIES))],
            'status' => ['nullable', Rule::in(['active', 'completed', 'all'])],
            'urgent' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);

        $sort = $filters['sort'] ?? 'popular';
        $status = $filters['status'] ?? 'active';

        $campaigns = Campaign::query()
            ->with('campaigner')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q
                    ->where('title', 'like', $like)
                    ->orWhere('summary', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhereHas('campaigner', fn ($c) => $c->where('name', 'like', $like)));
            })
            ->when($filters['category'] ?? null, fn ($query, $categories) => $query->whereIn('category', $categories))
            ->when($request->boolean('urgent'), fn ($query) => $query->where('is_urgent', true))
            ->when($sort, fn ($query) => match ($sort) {
                'newest' => $query->latest(),
                'ending' => $query->orderByRaw('deadline is null')->orderBy('deadline'),
                'most_funded' => $query->orderByDesc('collected_amount'),
                'least_funded' => $query->orderByRaw('cast(collected_amount as real) / target_amount asc'),
                'target_high' => $query->orderByDesc('target_amount'),
                default => $query->orderByDesc('donor_count'),
            })
            ->paginate(9)
            ->withQueryString();

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'filters' => $filters + ['sort' => $sort, 'status' => $status],
            'sorts' => self::SORTS,
        ]);
    }

    public function show(Request $request, Campaign $campaign): View
    {
        $campaign->load([
            'campaigner' => fn ($query) => $query->withCount('campaigns'),
            'withdrawals' => fn ($query) => $query->latest('withdrawn_at'),
        ]);

        $recentDonations = $campaign->donations()
            ->where('status', Donation::STATUS_PAID)
            ->latest('paid_at')
            ->take(5)
            ->get();

        $comments = $campaign->comments()
            ->with(['user', 'donation'])
            ->latest()
            ->paginate(5);

        $commentCount = $comments->total();

        $user = $request->user();
        $eligibleDonation = null;
        $amenedIds = [];

        if ($user) {
            $eligibleDonation = $user->donations()
                ->where('campaign_id', $campaign->id)
                ->where('status', Donation::STATUS_PAID)
                ->whereDoesntHave('comment')
                ->latest('paid_at')
                ->first();

            $amenedIds = $user->amenedComments()
                ->where('comments.campaign_id', $campaign->id)
                ->pluck('comments.id')
                ->all();
        }

        $related = Campaign::active()
            ->with('campaigner')
            ->where('category', $campaign->category)
            ->whereKeyNot($campaign->id)
            ->take(3)
            ->get();

        return view('campaigns.show', compact(
            'campaign', 'recentDonations', 'comments', 'commentCount', 'eligibleDonation', 'amenedIds', 'related'
        ));
    }
}
