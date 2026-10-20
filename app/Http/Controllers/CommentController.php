<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommentController extends Controller
{
    /**
     * Next page of prayers, rendered as HTML for the "load more" button.
     */
    public function index(Request $request, Campaign $campaign): View
    {
        abort_if($campaign->isDraft(), 404);

        $comments = $campaign->comments()->with(['user', 'donation'])->latest()->paginate(5);

        $amenedIds = $request->user()
            ? $request->user()->amenedComments()
                ->where('comments.campaign_id', $campaign->id)->pluck('comments.id')->all()
            : [];

        return view('campaigns.partials.comment-page', compact('comments', 'amenedIds'));
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_if($campaign->isDraft(), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $donation = $request->user()->donations()
            ->where('campaign_id', $campaign->id)
            ->where('status', Donation::STATUS_PAID)
            ->whereDoesntHave('comment')
            ->latest('paid_at')
            ->first();

        if (! $donation) {
            return back()->with('error', __('You can leave a prayer once you have donated to this campaign.'));
        }

        $comment = new Comment(['body' => strip_tags($validated['body'])]);
        $comment->campaign()->associate($campaign);
        $comment->user()->associate($request->user());
        $comment->donation()->associate($donation);
        $comment->save();

        return redirect()->to(route('campaigns.show', $campaign).'#prayers')
            ->with('status', __('Your prayer has been shared. Aamiin.'));
    }

    public function amen(Request $request, Comment $comment): JsonResponse
    {
        $userId = $request->user()->id;

        $amened = DB::transaction(function () use ($comment, $userId) {
            $exists = DB::table('comment_amens')
                ->where('comment_id', $comment->id)
                ->where('user_id', $userId)
                ->exists();

            if ($exists) {
                DB::table('comment_amens')->where('comment_id', $comment->id)->where('user_id', $userId)->delete();
                $comment->decrement('amens_count');

                return false;
            }

            DB::table('comment_amens')->insert(['comment_id' => $comment->id, 'user_id' => $userId, 'created_at' => now()]);
            $comment->increment('amens_count');

            return true;
        });

        return response()->json([
            'amened' => $amened,
            'count' => max(0, $comment->fresh()->amens_count),
        ]);
    }
}
