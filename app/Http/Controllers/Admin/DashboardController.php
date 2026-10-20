<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\News;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $paid = Donation::where('status', Donation::STATUS_PAID);

        return view('admin.dashboard', [
            'stats' => [
                'raised' => (int) (clone $paid)->sum('amount'),
                'donations' => (clone $paid)->count(),
                'users' => User::count(),
                'campaigns' => Campaign::active()->count(),
                'news' => News::count(),
                'pending' => Donation::where('status', Donation::STATUS_PENDING)->count(),
            ],
            'recentDonations' => Donation::with('campaign')->latest()->take(8)->get(),
            'latestNews' => News::latest()->take(5)->get(),
        ]);
    }
}
