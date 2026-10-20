<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Donation::STATUSES)],
        ]);

        $donations = Donation::with('campaign')
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q->where('reference', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('donor_name', 'like', $like));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.donations', compact('donations', 'filters'));
    }
}
