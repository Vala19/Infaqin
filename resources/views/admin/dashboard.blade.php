<x-layouts.admin title="Dashboard">
    <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['Total raised', rupiah($stats['raised']), 'wallet'],
            ['Successful donations', number_format($stats['donations']), 'receipt'],
            ['Waiting payment', number_format($stats['pending']), 'clock'],
            ['Registered users', number_format($stats['users']), 'users'],
            ['Active campaigns', number_format($stats['campaigns']), 'heart'],
            ['News articles', number_format($stats['news']), 'newspaper'],
        ] as [$label, $value, $icon])
            <div class="card flex items-center gap-4 p-5">
                <span class="grid size-12 place-items-center rounded-xl bg-mint-50 text-brand"><x-icon :name="$icon" class="size-6" /></span>
                <div><dt class="text-xs text-ink/60">{{ $label }}</dt><dd class="text-xl font-bold text-night">{{ $value }}</dd></div>
            </div>
        @endforeach
    </dl>

    <div class="mt-8 grid gap-8 xl:grid-cols-[1.5fr_1fr]">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-mist/60 px-5 py-4">
                <h2 class="font-semibold text-night">Recent donations</h2>
                <a href="{{ route('admin.donations.index') }}" class="text-sm font-medium text-brand hover:underline">View all</a>
            </div>
            <ul class="divide-y divide-mist/60">
                @foreach ($recentDonations as $d)
                    <li class="flex items-center gap-4 px-5 py-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-night">{{ $d->donor_name }} @if ($d->is_anonymous)<span class="text-xs text-ink/50">(anonymous)</span>@endif</p>
                            <p class="truncate text-xs text-ink/60">{{ $d->campaign->title }} · {{ $d->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="font-semibold text-night">{{ rupiah($d->amount) }}</span>
                        <x-status-badge :status="$d->status" />
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-mist/60 px-5 py-4">
                <h2 class="font-semibold text-night">Latest news</h2>
                <a href="{{ route('admin.news.create') }}" class="btn-primary h-9 px-3 text-xs"><x-icon name="plus" class="size-4" /> Write news</a>
            </div>
            <ul class="divide-y divide-mist/60">
                @forelse ($latestNews as $article)
                    <li class="flex items-center gap-3 px-5 py-3 text-sm">
                        <img src="{{ $article->imageUrl() }}" alt="" class="size-10 rounded-lg object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-night">{{ $article->title }}</p>
                            <p class="text-xs text-ink/60">{{ $article->published_at?->format('d M Y') ?? 'Draft' }}</p>
                        </div>
                        <a href="{{ route('admin.news.edit', $article) }}" class="text-ink/50 hover:text-brand" aria-label="Edit"><x-icon name="pencil" class="size-4" /></a>
                    </li>
                @empty
                    <li class="px-5 py-6 text-sm text-ink/60">No news yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
