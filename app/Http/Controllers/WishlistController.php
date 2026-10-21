<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Saved campaigns, newest first. Drafts never show; closed campaigns stay with a "Closed" label.
     */
    public function index(Request $request): View
    {
        $campaigns = $request->user()->wishlist()
            ->visible()
            ->with(['campaigner', 'category'])
            ->orderByPivot('created_at', 'desc')
            ->orderByDesc('wishlists.id')
            ->paginate(9);

        return view('wishlist', compact('campaigns'));
    }

    /**
     * Where a guest's heart points: `auth` sends them to log in, then they land back on the campaign.
     */
    public function campaign(Campaign $campaign): RedirectResponse
    {
        abort_if($campaign->isDraft(), 404);

        return redirect()->route('campaigns.show', $campaign);
    }

    /**
     * Sets (not flips) the saved state, so a repeated request leaves the same result.
     */
    public function update(Request $request, Campaign $campaign): JsonResponse|RedirectResponse
    {
        abort_if($campaign->isDraft(), 404);

        $request->validate([
            'saved' => ['required', 'boolean'],
        ]);

        $saved = $request->boolean('saved');
        $user = $request->user();

        if ($saved) {
            // The unique (user_id, campaign_id) index makes a concurrent duplicate a no-op.
            DB::table('wishlists')->insertOrIgnore([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $user->wishlist()->detach($campaign->id);
        }

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved]);
        }

        return back()->with('status', $saved ? __('Saved to your wishlist.') : __('Removed from your wishlist.'));
    }
}
