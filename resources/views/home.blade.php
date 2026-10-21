@php
    $user = auth()->user();
    $hour = now()->hour;
    $greeting = $hour < 11 ? __('Good morning') : ($hour < 15 ? __('Good afternoon') : ($hour < 18 ? __('Good evening') : __('Good night')));
@endphp

<x-layouts.app :title="__('Home')">
    {{-- ================= HERO ================= --}}
    <section class="relative overflow-hidden bg-forest-900 text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(34,176,75,0.35),transparent_60%)]"></div>
        <div class="container-page relative grid items-center gap-10 py-14 lg:grid-cols-[1.2fr_1fr] lg:py-20">
            <div>
                <p class="text-sm text-mint">{{ $greeting }}, {{ Str::before($user->name, ' ') }}</p>
                <h1 class="mt-3 text-3xl leading-tight font-bold tracking-tight sm:text-4xl lg:text-5xl">{{ __('Who will you help today?') }}</h1>
                <p class="mt-4 max-w-xl text-white/75">{{ __('Every donation you make is tracked on the Open Fund Ledger, so you can follow your kindness all the way to the people who receive it.') }}</p>

                @unless ($user->hasVerifiedEmail())
                    <div class="mt-6 flex max-w-xl flex-wrap items-center gap-3 rounded-xl bg-amber-400/15 px-4 py-3 text-sm text-amber-100 ring-1 ring-amber-300/30">
                        <x-icon name="mail" class="size-5 shrink-0" />
                        <span class="flex-1">{{ __('Please verify your email to secure your account.') }}</span>
                        <a href="{{ route('profile.edit') }}#email" class="font-semibold text-white underline underline-offset-2">{{ __('Verify now') }}</a>
                    </div>
                @endunless

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('campaigns.index') }}" class="btn-primary h-12 px-6">{{ __('Find a campaign') }} <x-icon name="arrow-right" class="size-4" /></a>
                    <a href="{{ route('landing') }}#features" class="btn h-12 border border-white/30 px-6 text-white hover:bg-white/10"><x-icon name="calculator" class="size-4" /> {{ __('Zakat calculator') }}</a>
                </div>
            </div>

            <dl class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                <div class="flex items-center gap-4 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-white"><x-figma-icon name="donation" class="size-7" /></span>
                    <div><dt class="text-xs text-white/60">{{ __('Total given') }}</dt><dd class="text-xl font-bold">{{ rupiah($stats['total']) }}</dd></div>
                </div>
                <div class="flex items-center gap-4 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-white"><x-figma-icon name="care" class="size-7" /></span>
                    <div><dt class="text-xs text-white/60">{{ __('Campaigns supported') }}</dt><dd class="text-xl font-bold">{{ $stats['campaigns'] }}</dd></div>
                </div>
                <div class="flex items-center gap-4 rounded-2xl bg-linear-to-r from-brand/30 to-transparent p-5 ring-1 ring-brand/40">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-brand text-white"><x-icon name="flame" class="size-7" /></span>
                    <div>
                        <dt class="text-xs text-white/60">{{ __('Kindness streak') }}</dt>
                        <dd class="text-xl font-bold">{{ trans_choice(':count week|:count weeks', $stats['streak']) }}</dd>
                    </div>
                </div>
            </dl>
        </div>
    </section>

    {{-- ================= SHORTCUTS ================= --}}
    <section class="relative z-10 container-page -mt-6" aria-label="{{ __('Shortcuts') }}">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['profile.edit', 'profile', __('Profile'), __('Name, birthday & email verification')],
                ['campaigns.index', 'donation', __('Campaigns'), __('Search, filter & donate in seconds')],
                ['history', 'history', __('History'), __('Every donation, receipt & status')],
                ['wishlist', 'heart', __('Wishlist'), __('Campaigns you saved for later')],
            ] as [$route, $icon, $label, $desc])
                <a href="{{ route($route) }}" class="group card flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-xl hover:ring-2 hover:ring-brand">
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-mint-50 transition group-hover:bg-mint/40">
                        @if ($icon === 'heart')
                            <x-icon name="heart" class="size-8 fill-current text-red-500" />
                        @else
                            <x-figma-icon :name="$icon" class="size-8" />
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-night">{{ $label }}</span>
                        <span class="block truncate text-xs text-ink/65">{{ $desc }}</span>
                    </span>
                    <x-icon name="chevron-right" class="size-5 text-ink/40 transition group-hover:translate-x-1 group-hover:text-brand" />
                </a>
            @endforeach
        </div>
    </section>

    {{-- ================= CAMPAIGNS + CTA BOX ================= --}}
    <section class="py-20">
        <div class="container-page">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <x-section-heading :eyebrow="__('Campaigns')" :title="__('Most loved campaigns')" align="left">
                    {{ __('The campaigns our community is rallying behind this week.') }}
                </x-section-heading>
                <a href="{{ route('campaigns.index') }}" class="btn-outline shrink-0">{{ __('Explore all') }} <x-icon name="arrow-right" class="size-4" /></a>
            </div>

            <div class="mt-10 grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($topCampaigns as $campaign)
                    <x-campaign-card :campaign="$campaign" :saved="in_array($campaign->id, $wishlistIds)" />
                @endforeach
            </div>

            @if ($urgentCampaign)
                <div class="mt-12 overflow-hidden rounded-3xl bg-brand text-white shadow-glow md:grid md:grid-cols-[280px_1fr]">
                    <div class="relative h-56 md:h-full">
                        <img src="{{ $urgentCampaign->imageUrl() }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                    </div>
                    <div class="flex flex-col gap-6 p-8 lg:flex-row lg:items-center lg:p-10">
                        <div class="flex-1">
                            <p class="flex items-center gap-2 text-xs font-semibold tracking-[0.2em] text-white/85 uppercase">
                                <span class="size-2 animate-pulse rounded-full bg-white"></span> {{ __('Needs you now') }}
                            </p>
                            <h3 class="mt-2 text-2xl leading-snug font-bold">{{ $urgentCampaign->title }}</h3>
                            <p class="mt-2 text-sm text-white/85">
                                {{ __('Only :days days left and :amount still needed.', ['days' => $urgentCampaign->daysLeft(), 'amount' => rupiah(max(0, $urgentCampaign->target_amount - $urgentCampaign->collected_amount))]) }}
                                {{ __('Even :amount brings them closer.', ['amount' => rupiah(25000)]) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col">
                            <a href="{{ route('donations.create', $urgentCampaign) }}" class="btn h-12 bg-white px-7 text-brand hover:bg-mint-50">{{ __('Donate now') }}</a>
                            <a href="{{ route('campaigns.show', $urgentCampaign) }}" class="btn h-12 border border-white/50 px-7 text-white hover:bg-white/10">{{ __('Read story') }}</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- ================= PRAYER WALL ================= --}}
    <section class="bg-white py-20">
        <div class="container-page">
            <x-section-heading :eyebrow="__('Prayer wall')" :title="__('Prayers from fellow donors')">
                {{ __('After donating, donors leave a prayer for the people they helped. Answer one with an “Aamiin”.') }}
            </x-section-heading>

            @if ($prayers->isEmpty())
                <p class="mt-10 text-center text-sm text-ink/60">{{ __('No prayers yet — be the first after your next donation.') }}</p>
            @else
                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($prayers as $prayer)
                        <x-prayer-card :comment="$prayer" :amened="in_array($prayer->id, $amenedIds)" :show-campaign="true" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ================= NEWS ================= --}}
    <section class="py-20">
        <div class="container-page">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <x-section-heading :eyebrow="__('News')" :title="__('What is happening around the world')" align="left">
                    {{ __('Stay informed about the people and places that need our help.') }}
                </x-section-heading>
                <a href="{{ route('news.index') }}" class="btn-outline shrink-0">{{ __('All news') }} <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="mt-10 grid gap-7 md:grid-cols-3">
                @forelse ($news as $article)
                    <x-news-card :article="$article" />
                @empty
                    <p class="text-sm text-ink/60">{{ __('No news yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>
