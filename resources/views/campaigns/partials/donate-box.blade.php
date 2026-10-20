<div class="card p-6">
    <p class="text-xs text-ink/60">{{ __('Collected') }}</p>
    <p class="text-3xl font-bold text-brand">{{ rupiah($campaign->collected_amount) }}</p>
    <p class="mt-0.5 text-sm text-ink/70">{{ __('of :amount target', ['amount' => rupiah($campaign->target_amount)]) }}</p>

    <x-progress :campaign="$campaign" size="lg" :show-amounts="false" class="mt-4" />

    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-xl bg-paper p-3">
            <dt class="text-[11px] text-ink/60">{{ __('Funded') }}</dt>
            <dd class="font-bold text-night">{{ $campaign->progress() }}%</dd>
        </div>
        <div class="rounded-xl bg-paper p-3">
            <dt class="text-[11px] text-ink/60">{{ __('Donors') }}</dt>
            <dd class="font-bold text-night">{{ number_format($campaign->donor_count) }}</dd>
        </div>
        <div class="rounded-xl bg-paper p-3">
            <dt class="text-[11px] text-ink/60">{{ __('Cashed out') }}</dt>
            <dd class="font-bold text-night">{{ $campaign->withdrawals->count() }}×</dd>
        </div>
    </dl>

    @if (! is_null($campaign->daysLeft()))
        <p class="mt-4 flex items-center gap-1.5 text-sm text-ink/70"><x-icon name="clock" class="size-4" />
            @if ($campaign->isOpen()) {{ __(':count days left', ['count' => $campaign->daysLeft()]) }} · {{ __('ends :date', ['date' => $campaign->deadline->translatedFormat('d M Y')]) }} @else {{ __('Campaign has ended') }} @endif
        </p>
    @endif

    <div class="mt-5 grid grid-cols-[1fr_auto] gap-2" x-data="shareButton(@js(route('campaigns.show', $campaign)), @js($campaign->title))">
        @if ($campaign->isOpen())
            <a href="{{ route('donations.create', $campaign) }}" class="btn-primary h-12 text-base"><x-icon name="heart" class="size-5" /> {{ __('Donate now') }}</a>
        @else
            <span class="btn h-12 cursor-not-allowed bg-mist text-ink/60">{{ __('Donations closed') }}</span>
        @endif

        <div class="relative" @click.outside="open = false">
            <button type="button" @click="share()" class="btn-outline h-12 w-12 px-0" aria-label="{{ __('Share this campaign') }}" :aria-expanded="open">
                <x-figma-icon name="share" class="size-5" />
            </button>
            <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 z-30 mt-2 w-60 rounded-xl bg-white p-2 text-sm shadow-xl ring-1 ring-black/5">
                <button type="button" @click="copy()" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left hover:bg-mint-50">
                    <x-icon name="link" class="size-4 text-brand" /> <span x-text="copied ? @js(__('Link copied!')) : @js(__('Copy link'))">{{ __('Copy link') }}</span>
                </button>
                <a :href="'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url)" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 rounded-lg px-3 py-2 hover:bg-mint-50"><x-icon name="whatsapp" class="size-4 text-brand" /> WhatsApp</a>
                <a :href="'https://x.com/intent/post?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url)" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 rounded-lg px-3 py-2 hover:bg-mint-50"><x-icon name="x-social" class="size-4 text-brand" /> X</a>
                <a :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url)" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 rounded-lg px-3 py-2 hover:bg-mint-50"><x-icon name="facebook" class="size-4 text-brand" /> Facebook</a>
            </div>
        </div>
    </div>

    @if ($recentDonations->isNotEmpty())
        <div class="mt-6 border-t border-mist/60 pt-5">
            <p class="text-xs font-semibold tracking-wide text-ink/60 uppercase">{{ __('Recent donors') }}</p>
            <ul class="mt-3 space-y-3">
                @foreach ($recentDonations as $d)
                    <li class="flex items-center gap-3 text-sm">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-mint-50 text-[11px] font-bold text-forest">{{ $d->is_anonymous ? 'HA' : Str::upper(Str::substr($d->donor_name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1 truncate text-ink">{{ $d->publicName() }}</span>
                        <span class="shrink-0 font-semibold text-night">{{ rupiah_short($d->amount) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
