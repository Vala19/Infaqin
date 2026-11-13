<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public const INTENT_KEY = 'wishlist.intent';

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
     * Where a guest's heart points. The campaign is remembered in the session; LoginController
     * saves it after a successful login and lands back on the campaign.
     */
    public function campaign(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_if($campaign->isDraft(), 404);

        if ($request->user()) {
            return redirect()->route('campaigns.show', $campaign);
        }

        $request->session()->put(self::INTENT_KEY, $campaign->id);
        redirect()->setIntendedUrl(route('campaigns.show', $campaign));

        return redirect()->route('login');
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
            $user->saveToWishlist($campaign);
        } else {
            $user->wishlist()->detach($campaign->id);
        }

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved]);
        }

        return back()->with('status', $saved ? __('Saved to your wishlist.') : __('Removed from your wishlist.'));
    }
}
