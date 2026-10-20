<x-layouts.admin title="Campaigns">
    <div class="flex flex-col justify-between gap-3 sm:flex-row">
        <form method="GET" class="relative w-full sm:max-w-sm" role="search">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-ink/50" />
            <input type="search" name="q" value="{{ $term }}" placeholder="Search campaigns…" class="input h-11 pl-11" aria-label="Search campaigns">
        </form>
        <a href="{{ route('admin.campaigns.create') }}" class="btn-primary h-11"><x-icon name="plus" class="size-4" /> New campaign</a>
    </div>

    <div class="mt-6 overflow-x-auto card">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-paper text-xs tracking-wide text-ink/60 uppercase">
                <tr>
                    <th class="px-5 py-3 font-semibold">Campaign</th>
                    <th class="px-5 py-3 font-semibold">Progress</th>
                    <th class="px-5 py-3 font-semibold">Donors</th>
                    <th class="px-5 py-3 font-semibold">Cash-outs</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/60">
                @forelse ($campaigns as $campaign)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $campaign->imageUrl() }}" alt="" class="size-11 rounded-lg object-cover">
                                <div class="min-w-0">
                                    <p class="max-w-xs truncate font-medium text-night">{{ $campaign->title }}</p>
                                    <p class="text-xs text-ink/60">{{ $campaign->campaigner->name }} · {{ $campaign->categoryLabel() }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="w-48 px-5 py-3">
                            <x-progress :campaign="$campaign" size="sm" :show-amounts="false" />
                            <p class="mt-1 text-xs text-ink/60">{{ rupiah_short($campaign->collected_amount) }} / {{ rupiah_short($campaign->target_amount) }}</p>
                        </td>
                        <td class="px-5 py-3">{{ $campaign->donor_count }}</td>
                        <td class="px-5 py-3">{{ $campaign->withdrawals_count }}×</td>
                        <td class="px-5 py-3"><x-status-badge :status="$campaign->status" /></td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('campaigns.show', $campaign) }}" target="_blank" class="grid size-9 place-items-center rounded-lg text-ink/60 hover:bg-paper hover:text-brand" aria-label="View"><x-icon name="external" class="size-4" /></a>
                                <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="grid size-9 place-items-center rounded-lg text-ink/60 hover:bg-paper hover:text-brand" aria-label="Edit"><x-icon name="pencil" class="size-4" /></a>
                                <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete this campaign and all its donations? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="grid size-9 place-items-center rounded-lg text-ink/60 hover:bg-red-50 hover:text-red-600" aria-label="Delete"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-ink/60">No campaigns found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $campaigns->links() }}</div>
</x-layouts.admin>
