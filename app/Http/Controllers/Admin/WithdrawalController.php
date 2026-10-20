<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        $available = $campaign->collected_amount - (int) $campaign->withdrawals()->sum('amount');

        $data = $request->validateWithBag('withdrawal', [
            'amount' => ['required', 'integer', 'min:1', 'max:'.max(1, $available)],
            'description' => ['required', 'string', 'max:255'],
            'withdrawn_at' => ['required', 'date', 'before_or_equal:now'],
        ], [
            'amount.max' => 'Only '.rupiah($available).' is available to withdraw.',
        ]);

        $data['description'] = strip_tags($data['description']);
        $campaign->withdrawals()->create($data);

        return back()->with('status', 'Withdrawal recorded on the public ledger.');
    }

    public function destroy(Withdrawal $withdrawal): RedirectResponse
    {
        $withdrawal->delete();

        return back()->with('status', 'Withdrawal removed.');
    }
}
