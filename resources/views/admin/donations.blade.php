<x-layouts.admin title="Donations">
    <form method="GET" class="flex flex-col gap-3 sm:flex-row" role="search">
        <div class="relative flex-1 sm:max-w-sm">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-ink/50" />
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Reference, name or email…" class="input h-11 pl-11" aria-label="Search donations">
        </div>
        <select name="status" class="input h-11 sm:w-48" onchange="this.form.requestSubmit()" aria-label="Status">
            <option value="">All statuses</option>
            @foreach (\App\Models\Donation::STATUSES as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button class="btn-primary h-11">Search</button>
    </form>

    <div class="mt-6 overflow-x-auto card">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="bg-paper text-xs tracking-wide text-ink/60 uppercase">
                <tr>
                    <th class="px-5 py-3 font-semibold">Reference</th>
                    <th class="px-5 py-3 font-semibold">Donor</th>
                    <th class="px-5 py-3 font-semibold">Campaign</th>
                    <th class="px-5 py-3 text-right font-semibold">Amount</th>
                    <th class="px-5 py-3 font-semibold">Method</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/60">
                @forelse ($donations as $d)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs">{{ $d->reference }}</td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-night">{{ $d->donor_name }} @if ($d->is_anonymous)<span class="text-xs text-ink/50">(anon)</span>@endif</p>
                            <p class="text-xs text-ink/60">{{ $d->email }}</p>
                        </td>
                        <td class="max-w-[220px] truncate px-5 py-3 text-ink/80">{{ $d->campaign->title }}</td>
                        <td class="px-5 py-3 text-right font-semibold whitespace-nowrap">{{ rupiah($d->amount) }}</td>
                        <td class="px-5 py-3 whitespace-nowrap text-ink/80">{{ $d->paymentMethodLabel() }}</td>
                        <td class="px-5 py-3"><x-status-badge :status="$d->status" /></td>
                        <td class="px-5 py-3 whitespace-nowrap text-ink/80">{{ $d->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-ink/60">No donations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $donations->links() }}</div>
</x-layouts.admin>
