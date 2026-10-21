@props(['campaign', 'popover' => true, 'saved' => false])

{{-- Campaign card with a hover/focus popover that previews the basic details. --}}
<article x-data="{ peek: false, timer: null }"
         @mouseenter="clearTimeout(timer); timer = setTimeout(() => peek = true, 1800)"
         @mouseleave="clearTimeout(timer); peek = false"
         @focusin="peek = true" @focusout="peek = false"
         @keydown.escape="peek = false"
         {{ $attributes->merge(['class' => 'group relative flex flex-col card transition duration-300 hover:-translate-y-1 hover:shadow-xl']) }}>
    <a href="{{ route('campaigns.show', $campaign) }}" class="relative block aspect-[361/310] overflow-hidden rounded-t-2xl bg-mist">
        <img src="{{ $campaign->imageUrl() }}" alt="{{ $campaign->title }}" loading="lazy"
             class="size-full object-cover transition duration-500 group-hover:scale-105">
        <div class="absolute top-3 right-15 left-3 flex flex-wrap gap-1.5">
            <span class="chip {{ $campaign->isProgram() ? 'bg-forest text-white' : 'bg-white/95 text-forest' }} shadow-sm" data-campaign-type="card">{{ $campaign->typeLabel() }}</span>
            <span class="chip bg-white/95 text-forest shadow-sm">{{ $campaign->categoryLabel() }}</span>
            @if ($campaign->is_urgent)
                <span class="chip bg-red-600 text-white shadow-sm">{{ __('Urgent') }}</span>
            @endif
        </div>
    </a>
    <x-wishlist-button :campaign="$campaign" :saved="$saved" class="absolute top-3 right-3 z-10" />

    <div class="flex flex-1 flex-col p-5">
        <p class="flex items-center gap-1.5 text-xs text-ink/70">
            <span class="truncate">{{ $campaign->campaigner->name }}</span>
            @if ($campaign->campaigner->is_verified)
                <x-icon name="shield-check" class="size-3.5 shrink-0 text-brand" />
            @endif
        </p>
        <h3 class="mt-1 line-clamp-2 text-base leading-snug font-semibold text-night">
            <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-brand focus:outline-none">{{ $campaign->title }}</a>
        </h3>

        <x-progress :campaign="$campaign" size="sm" class="mt-auto pt-4" />

        <div class="mt-3 flex items-center justify-between text-xs text-ink/70">
            <span class="flex items-center gap-1"><x-icon name="users" class="size-4" /> {{ __(':count donors', ['count' => number_format($campaign->donor_count)]) }}</span>
            @if (! is_null($campaign->daysLeft()))
                <span class="flex items-center gap-1"><x-icon name="clock" class="size-4" /> {{ __(':count days left', ['count' => $campaign->daysLeft()]) }}</span>
            @endif
        </div>

        {{ $slot }}
    </div>

    @if ($popover)
        <div x-cloak x-show="peek"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0"
             class="pointer-events-none absolute inset-x-3 top-3 z-20 hidden rounded-xl bg-night/95 p-4 text-white shadow-2xl ring-1 ring-white/10 backdrop-blur md:block"
             role="tooltip">
            <p class="text-[11px] font-semibold tracking-[0.18em] text-mint uppercase">{{ __('Quick look') }}</p>
            <p class="mt-1 text-sm leading-snug font-semibold">{{ $campaign->title }}</p>
            <p class="mt-2 line-clamp-3 text-xs leading-relaxed text-white/75">{{ $campaign->summary }}</p>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-lg bg-white/5 p-2">
                    <dt class="text-white/50">{{ __('Funded') }}</dt>
                    <dd class="font-semibold text-mint">{{ $campaign->progress() }}%</dd>
                </div>
                <div class="rounded-lg bg-white/5 p-2">
                    <dt class="text-white/50">{{ __('Still needed') }}</dt>
                    <dd class="font-semibold">{{ rupiah_short(max(0, $campaign->target_amount - $campaign->collected_amount)) }}</dd>
                </div>
                @if ($campaign->location)
                    <div class="col-span-2 flex items-center gap-1.5 text-white/70">
                        <x-icon name="map-pin" class="size-3.5" /> {{ $campaign->location }}
                    </div>
                @endif
            </dl>
        </div>
    @endif
</article>
