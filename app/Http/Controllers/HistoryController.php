<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public const SORTS = [
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'amount_desc' => 'Highest amount',
        'amount_asc' => 'Lowest amount',
    ];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Donation::STATUSES)],
            'method' => ['nullable', Rule::in(array_keys(Donation::PAYMENT_METHODS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);

        $sort = $filters['sort'] ?? 'newest';

        $query = $request->user()->donations()
            ->with('campaign')
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q
                    ->where('reference', 'like', $like)
                    ->orWhere('on_behalf_of', 'like', $like)
                    ->orWhereHas('campaign', fn ($c) => $c->where('title', 'like', $like)));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to));

        $summary = [
            'count' => (clone $query)->count(),
            'paid' => (int) (clone $query)->where('status', Donation::STATUS_PAID)->sum('amount'),
        ];

        $donations = match ($sort) {
            'oldest' => $query->oldest(),
            'amount_desc' => $query->orderByDesc('amount'),
            'amount_asc' => $query->orderBy('amount'),
            default => $query->latest(),
        };

        return view('history', [
            'donations' => $donations->paginate(10)->withQueryString(),
            'filters' => $filters + ['sort' => $sort],
            'summary' => $summary,
            'sorts' => self::SORTS,
            'methods' => Donation::PAYMENT_METHODS,
        ]);
    }
}
