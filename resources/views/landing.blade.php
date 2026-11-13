@php
    $company = config('infaqin.company');
    $featureIcons = ['ledger' => 'receipt', 'prayer' => 'hands', 'zakat' => 'calculator', 'streak' => 'flame', 'tribute' => 'gift', 'anonymous' => 'mask'];
    // Icon tiles take turns through the brand colours, so the six cards are not one repeated block.
    $featureTiles = ['bg-brand text-white', 'bg-forest text-mint', 'bg-mint text-forest'];
@endphp

<x-layouts.app nav="landing" :overlay-nav="true">
    {{-- ================= HERO: campaign carousel ================= --}}
    <section id="top" data-section class="relative overflow-hidden bg-forest-900 text-white"
             x-data="carousel({{ $slides->count() }})" @mouseenter="stop()" @mouseleave="play()"
             aria-roledescription="carousel" aria-label="{{ __('Featured campaigns') }}">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(34,176,75,0.35),transparent_55%)]"></div>
        <div class="pointer-events-none absolute -bottom-40 -left-40 size-[520px] rounded-full bg-brand/10 blur-3xl"></div>

        <div class="container-page relative grid min-h-[640px] items-center gap-10 pt-28 pb-20 lg:min-h-[720px] lg:grid-cols-[1.05fr_0.95fr] lg:pt-32">
            {{-- Slide copy --}}
            {{-- All slides share one grid cell, so the hero is as tall as the longest slide. --}}
            <div class="grid">
                @foreach ($slides as $i => $slide)
                    <div class="col-start-1 row-start-1 transition duration-700 ease-out" @if ($i > 0) x-cloak @endif
                         :class="active === {{ $i }} ? 'visible translate-y-0 opacity-100' : 'invisible translate-y-4 opacity-0'"
                         :aria-hidden="active !== {{ $i }}"
                         role="group" aria-roledescription="slide" aria-label="{{ __(':current of :total', ['current' => $i + 1, 'total' => $slides->count()]) }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip bg-white/10 text-mint ring-1 ring-mint/30">{{ $slide->categoryLabel() }}</span>
                            @if ($slide->is_urgent)
                                <span class="chip bg-red-500/90 text-white">{{ __('Urgent') }}</span>
                            @endif
                            <span class="chip text-white/70"><x-icon name="map-pin" class="size-3.5" /> {{ $slide->location }}</span>
                        </div>
                        <h1 class="mt-5 text-4xl leading-[1.1] font-bold tracking-tight text-balance sm:text-5xl lg:text-[3.4rem]">{{ $slide->title }}</h1>
                        <p class="mt-5 max-w-xl text-base leading-relaxed text-white/75 sm:text-lg">{{ $slide->summary }}</p>
                        <x-progress :campaign="$slide" :dark="true" size="md" class="mt-7 max-w-md" />
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ auth()->check() ? route('donations.create', $slide) : route('campaigns.show', $slide) }}" class="btn-primary h-12 px-7 text-base">
                                {{ __('Donate now') }} <x-icon name="arrow-right" class="size-4" />
                            </a>
                            <a href="{{ route('campaigns.show', $slide) }}" class="btn h-12 border border-white/30 px-6 text-base text-white hover:bg-white/10">{{ __('See details') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Slide imagery --}}
            <div class="relative mx-auto w-full max-w-[520px]">
                <div class="absolute -inset-4 rotate-3 rounded-[2rem] bg-brand/25"></div>
                <div class="relative aspect-[361/310] overflow-hidden rounded-[1.75rem] bg-forest shadow-2xl ring-1 ring-white/10">
                    @foreach ($slides as $i => $slide)
                        <img src="{{ $slide->imageUrl() }}" alt="{{ $slide->title }}"
                             x-show="active === {{ $i }}" @if ($i > 0) x-cloak loading="lazy" @endif
                             x-transition:enter="transition duration-700" x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100"
                             class="absolute inset-0 size-full object-cover">
                    @endforeach
                </div>
                {{-- Who runs the campaign on screen, and how it is going --}}
                <div class="absolute -bottom-6 -left-4 grid max-w-[calc(100%-1rem)] rounded-2xl bg-white px-4 py-3 text-night shadow-xl sm:-left-8">
                    @foreach ($slides as $i => $slide)
                        <div class="col-start-1 row-start-1 flex items-center gap-3 transition-opacity duration-500" @if ($i > 0) x-cloak @endif
                             :class="active === {{ $i }} ? 'visible opacity-100' : 'invisible opacity-0'" :aria-hidden="active !== {{ $i }}">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-mint-50"><x-figma-icon name="donation" class="size-6" /></span>
                            <div class="min-w-0">
                                <p class="flex items-center gap-1 text-xs text-ink/70">
                                    <span class="truncate">{{ $slide->campaigner->name }}</span>
                                    @if ($slide->campaigner->is_verified)
                                        <x-icon name="shield-check" class="size-3.5 shrink-0 text-brand" />
                                    @endif
                                </p>
                                <p class="text-sm font-bold whitespace-nowrap">
                                    {{ __(':count donors', ['count' => number_format($slide->donor_count)]) }}@if (! is_null($slide->daysLeft())) · {{ __(':count days left', ['count' => $slide->daysLeft()]) }}@endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Carousel controls: the active bar fills while its slide is on screen --}}
        @if ($slides->count() > 1)
            <div class="container-page relative -mt-10 flex flex-wrap items-center gap-4 pb-12">
                <button type="button" @click="prev()" class="grid size-10 place-items-center rounded-full border border-white/25 text-white transition hover:bg-white/10" aria-label="{{ __('Previous campaign') }}"><x-icon name="chevron-left" class="size-5" /></button>
                <div class="flex items-center gap-2">
                    @foreach ($slides as $i => $slide)
                        <button type="button" @click="go({{ $i }})" class="group grid h-6 place-items-center transition-all duration-300"
                                :class="active === {{ $i }} ? 'w-12' : 'w-6'" :aria-current="active === {{ $i }}"
                                aria-label="{{ __('Show slide :number', ['number' => $i + 1]) }}: {{ $slide->title }}">
                            <span class="block h-1.5 w-full overflow-hidden rounded-full bg-white/25 group-hover:bg-white/45">
                                <span class="block h-full origin-left rounded-full bg-brand" :style="progressStyle({{ $i }})" style="transform: scaleX({{ $i === 0 ? 1 : 0 }})"></span>
                            </span>
                        </button>
                    @endforeach
                </div>
                <button type="button" @click="next()" class="grid size-10 place-items-center rounded-full border border-white/25 text-white transition hover:bg-white/10" aria-label="{{ __('Next campaign') }}"><x-icon name="chevron-right" class="size-5" /></button>
                <button type="button" @click="toggle()" class="ml-1 flex h-10 items-center gap-2 rounded-full px-3 text-sm text-white/80 transition hover:bg-white/10 hover:text-white">
                    <span class="relative grid size-4 place-items-center" aria-hidden="true">
                        <svg x-show="!paused" class="size-4" viewBox="0 0 16 16" fill="currentColor"><rect x="3" y="2" width="3.5" height="12" rx="1" /><rect x="9.5" y="2" width="3.5" height="12" rx="1" /></svg>
                        <svg x-show="paused" x-cloak class="size-4" viewBox="0 0 16 16" fill="currentColor"><path d="M4 2.5v11a1 1 0 0 0 1.5.86l9-5.5a1 1 0 0 0 0-1.72l-9-5.5A1 1 0 0 0 4 2.5Z" /></svg>
                    </span>
                    <span class="max-sm:sr-only" x-text="paused ? @js(__('Play')) : @js(__('Pause'))">{{ __('Pause') }}</span>
                </button>
            </div>
        @endif
    </section>

    {{-- Impact strip --}}
    <div class="relative z-10 container-page -mt-8">
        <dl class="grid grid-cols-1 divide-y divide-mist/70 card sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            @foreach ([['donation', __('Raised for people in need'), rupiah($stats['raised'])], ['profile', __('Generous donors'), number_format($stats['donors'])], ['care', __('Campaigns supported'), number_format($stats['campaigns'])]] as [$icon, $label, $value])
                <div class="flex items-center justify-center gap-4 px-6 py-5">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-mint-50"><x-figma-icon :name="$icon" class="size-7" /></span>
                    <div class="flex flex-col">
                        <dt class="order-2 text-sm text-ink/70">{{ $label }}</dt>
                        <dd class="order-1 text-2xl font-bold text-brand tabular-nums">{{ $value }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- ================= ABOUT: vision & mission ================= --}}
    <section id="about" data-section class="py-24">
        <div class="container-page grid items-center gap-14 lg:grid-cols-2">
            <div class="relative">
                {{-- Three photographs, so the column is as tall as the text beside it --}}
                <div class="grid grid-cols-2 gap-4">
                    <img src="{{ asset('images/campaigns/team.jpg') }}" alt="{{ __('The Infaqin volunteer team') }}" width="1400" height="933" class="col-span-2 aspect-[16/9] w-full rounded-2xl object-cover object-[50%_40%] shadow-card" loading="lazy">
                    <img src="{{ asset('images/campaigns/handover.jpg') }}" alt="{{ __('A volunteer handing over aid') }}" width="933" height="1400" class="aspect-[4/5] w-full rounded-2xl object-cover object-[50%_45%] shadow-card" loading="lazy">
                    <img src="{{ asset('images/campaigns/volunteer-gift.jpg') }}" alt="{{ __('A volunteer holding a gift basket') }}" width="933" height="1400" class="aspect-[4/5] w-full rounded-2xl object-cover object-[50%_45%] shadow-card" loading="lazy">
                </div>
                <div class="absolute -right-2 -bottom-8 hidden items-center gap-3 rounded-2xl bg-forest p-4 pr-6 text-white shadow-xl sm:flex">
                    <x-logo variant="tile" class="h-12 rounded-xl" />
                    <div>
                        <p class="text-xs text-white/70">{{ __('Since 2016') }}</p>
                        <p class="font-semibold">{{ __('Verified campaigners only') }}</p>
                    </div>
                </div>
            </div>

            <div>
                <x-section-heading :eyebrow="__('About us')" :title="__('Giving that travels straight to the heart')" align="left">
                    {{ __('Infaqin was born from a simple belief: generosity grows when people can see where it goes. We partner with verified foundations and volunteers to deliver your infaq, sedekah and zakat to the people who need it most.') }}
                </x-section-heading>

                <div class="mt-8 rounded-2xl border-l-4 border-brand bg-mint-50 p-6">
                    <h3 class="flex items-center gap-2 font-semibold text-forest"><x-icon name="eye" class="size-5" /> {{ __('Our vision') }}</h3>
                    <p class="mt-2 leading-relaxed text-ink">{{ __(config('infaqin.vision')) }}</p>
                </div>

                <h3 class="mt-8 flex items-center gap-2 font-semibold text-forest"><x-icon name="sparkles" class="size-5" /> {{ __('Our mission') }}</h3>
                <ul class="mt-4 space-y-3">
                    @foreach (config('infaqin.missions') as $i => $mission)
                        <li class="flex gap-3">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-white">{{ $i + 1 }}</span>
                            <span class="pt-0.5 leading-relaxed text-ink">{{ __($mission) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ================= TOP CAMPAIGNS ================= --}}
    <section id="campaigns" data-section class="bg-white py-24">
        <div class="container-page">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <x-section-heading :eyebrow="__('Campaigns')" :title="__('Most loved campaigns right now')" align="left">
                    {{ __('Chosen by the number of people who have already given. Hover a card for a quick look.') }}
                </x-section-heading>
                <a href="{{ route('campaigns.index') }}" class="btn-outline shrink-0">{{ __('View all campaigns') }} <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="mt-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($topCampaigns as $campaign)
                    <x-campaign-card :campaign="$campaign" :saved="in_array($campaign->id, $wishlistIds)" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= FEATURES ================= --}}
    <section id="features" data-section class="py-24">
        <div class="container-page">
            <x-section-heading :eyebrow="__('Features')" :title="__('Not just another donation page')">
                {{ __('Six things you will only find on Infaqin — built around trust, sincerity and the joy of giving together.') }}
            </x-section-heading>

            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach (config('infaqin.features') as $i => $feature)
                    <div class="group card flex flex-col p-7 transition hover:-translate-y-1 hover:shadow-xl">
                        <span @class(['grid size-14 place-items-center rounded-2xl transition group-hover:scale-105', $featureTiles[$i % count($featureTiles)]])>
                            <x-icon :name="$featureIcons[$feature['key']]" class="size-7" />
                        </span>
                        <h3 class="mt-5 text-lg font-semibold text-night">{{ __($feature['title']) }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink/80">{{ __($feature['body']) }}</p>

                        {{-- Live proof where the feature has some --}}
                        @if ($feature['key'] === 'ledger' && $latestWithdrawal)
                            <a href="{{ route('campaigns.show', $latestWithdrawal->campaign) }}" class="mt-6 block rounded-xl bg-mint-50 p-4 pt-3.5 transition hover:bg-mint/40 md:mt-auto">
                                <span class="block text-xs font-semibold text-forest">{{ __('Latest withdrawal') }}</span>
                                <span class="mt-1 flex items-baseline justify-between gap-3">
                                    <span class="truncate text-sm font-medium text-night">{{ $latestWithdrawal->campaign->title }}</span>
                                    <span class="shrink-0 text-sm font-bold text-forest tabular-nums">{{ rupiah($latestWithdrawal->amount) }}</span>
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-ink/70">
                                    <time datetime="{{ $latestWithdrawal->withdrawn_at->toDateString() }}">{{ $latestWithdrawal->withdrawn_at->translatedFormat('d M Y') }}</time>, {{ $latestWithdrawal->description }}
                                </span>
                            </a>
                        @elseif ($feature['key'] === 'prayer' && $latestPrayer)
                            <a href="{{ route('campaigns.show', $latestPrayer->campaign) }}#prayers" class="mt-6 block rounded-xl bg-mint-50 p-4 pt-3.5 transition hover:bg-mint/40 md:mt-auto">
                                <span class="block text-xs font-semibold text-forest">{{ __('Latest prayer') }}</span>
                                <span class="mt-1 line-clamp-2 text-sm text-night italic">“{{ $latestPrayer->body }}”</span>
                                <span class="mt-1 block truncate text-xs text-ink/70">{{ $latestPrayer->authorName() }}, {{ trans_choice(':count person said Aamiin|:count people said Aamiin', $latestPrayer->amens_count) }}</span>
                            </a>
                        @elseif ($feature['key'] === 'zakat')
                            <a href="#zakat" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline md:mt-auto">
                                {{ __('Try the calculator') }} <x-icon name="chevron-down" class="size-4" />
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Zakat calculator (live feature) --}}
            <div id="zakat" x-data="zakatCalculator" class="mt-14 scroll-mt-24 overflow-hidden rounded-3xl bg-forest text-white shadow-xl lg:grid lg:grid-cols-[1.15fr_1fr]">
                <div class="p-8 sm:p-10">
                    <p class="eyebrow text-mint">{{ __('Try it now') }}</p>
                    <h3 class="mt-2 text-2xl font-bold">{{ __('Zakat calculator') }}</h3>
                    <p class="mt-2 text-sm text-white/70">{{ __('Zakat mal is 2.5% of wealth held for a full year once it reaches the nisab (85 grams of gold).') }}</p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @foreach (['savings' => __('Cash & savings'), 'gold' => __('Gold & silver value'), 'investments' => __('Investments & receivables'), 'debts' => __('Debts due now')] as $field => $label)
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-medium text-white/80">{{ $label }}</span>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 grid place-items-center text-sm text-white/50">Rp</span>
                                    <input type="text" inputmode="numeric" x-model="{{ $field }}" placeholder="0"
                                           @input="$el.value = $el.value.replace(/\D/g, '') ? Number($el.value.replace(/\D/g, '')).toLocaleString('id-ID') : ''"
                                           class="w-full rounded-lg border border-white/15 bg-white/10 py-2.5 pr-3 pl-10 text-sm text-white placeholder:text-white/40 focus:border-mint focus:ring-2 focus:ring-mint/30 focus:outline-none">
                                </div>
                            </label>
                        @endforeach
                        <label class="block sm:col-span-2">
                            <span class="mb-1.5 block text-xs font-medium text-white/80">{{ __('Gold price per gram (edit to today\'s price)') }}</span>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 grid place-items-center text-sm text-white/50">Rp</span>
                                <input type="number" min="0" step="1000" x-model.number="goldPrice"
                                       class="w-full rounded-lg border border-white/15 bg-white/10 py-2.5 pr-3 pl-10 text-sm text-white focus:border-mint focus:ring-2 focus:ring-mint/30 focus:outline-none">
                            </div>
                        </label>
                    </div>
                </div>
                <div class="flex flex-col justify-center bg-forest-900 p-8 sm:p-10">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-white/60">{{ __('Total zakatable wealth') }}</dt><dd class="font-medium" x-text="rupiah(wealth)"></dd></div>
                        <div class="flex justify-between"><dt class="text-white/60">{{ __('Nisab (85 g gold)') }}</dt><dd class="font-medium" x-text="rupiah(nisab)"></dd></div>
                    </dl>
                    <div class="mt-6 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                        <p class="text-xs text-white/60">{{ __('Your zakat this year') }}</p>
                        <p class="mt-1 text-3xl font-bold text-mint" x-text="rupiah(zakat)">Rp0</p>
                        <p class="mt-2 text-xs text-white/60" x-show="!eligible">{{ __('Your wealth is below the nisab — zakat is not yet obligatory, but sedekah is always welcome.') }}</p>
                        <p class="mt-2 text-xs text-white/60" x-cloak x-show="eligible">{{ __('Your wealth has reached the nisab. May Allah bless and purify it.') }}</p>
                    </div>
                    <a :href="'{{ auth()->check() ? route('campaigns.index') : route('register') }}'" class="btn-primary mt-6 h-11">{{ __('Channel my zakat') }} <x-icon name="arrow-right" class="size-4" /></a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TESTIMONIALS ================= --}}
    <section id="testimonials" data-section class="bg-night py-24 text-white">
        <div class="container-page">
            <x-section-heading :eyebrow="__('Other say')" :title="__('Voices that believe in Infaqin')" :dark="true">
                {{ __('Public figures who give through Infaqin share why transparency matters to them.') }}
            </x-section-heading>
            <div class="mt-14 grid gap-6 md:grid-cols-2">
                @foreach (config('infaqin.testimonials') as $t)
                    <figure class="relative rounded-2xl bg-white/5 p-7 ring-1 ring-white/10 transition hover:bg-white/[0.08]">
                        <x-icon name="quote" class="absolute top-6 right-6 size-10 text-brand/40" />
                        <blockquote class="pr-10 text-[15px] leading-relaxed text-white/85">“{{ __($t['quote']) }}”</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3">
                            <span class="grid size-12 place-items-center rounded-full bg-linear-to-br from-brand to-forest text-sm font-bold">
                                {{ collect(explode(' ', $t['name']))->map(fn ($p) => $p[0])->take(2)->implode('') }}
                            </span>
                            <span>
                                <span class="block font-semibold">{{ $t['name'] }}</span>
                                <span class="block text-xs text-mint">{{ __($t['role']) }}</span>
                            </span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= APPEAL ================= --}}
    <section id="appeal" data-section class="relative overflow-hidden py-28">
        <img src="{{ asset('images/campaigns/volunteer-gift.jpg') }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
        <div class="absolute inset-0 bg-linear-to-r from-forest-900/95 via-forest-900/85 to-forest-900/60"></div>
        <div class="relative container-page max-w-4xl text-center text-white">
            <x-icon name="hands" class="mx-auto size-12 text-mint" />
            <p class="mt-6 text-3xl leading-snug font-semibold text-balance sm:text-4xl">
                {{ __('Somewhere tonight, a child is waiting for a book, a mother for clean water, a teacher for a little recognition.') }}
            </p>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-white/75">
                {{ __('You do not need to be rich to change a life — you only need to care. A single gift, a single prayer, can be the reason someone keeps going.') }}
                <span class="mt-3 block italic text-mint">“{{ __('The believer\'s shade on the Day of Resurrection will be their charity.') }}”</span>
            </p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('campaigns.index') }}" class="btn-primary h-12 px-8 text-base">{{ __('Start giving today') }}</a>
                @guest
                    <a href="{{ route('register') }}" class="btn h-12 border border-white/40 px-8 text-base text-white hover:bg-white/10">{{ __('Create a free account') }}</a>
                @endguest
            </div>
        </div>
    </section>

    {{-- ================= CONTACT ================= --}}
    <section id="contact" data-section class="bg-white py-24">
        <div class="container-page">
            <x-section-heading :eyebrow="__('Contact us')" :title="__('We would love to hear from you')">
                {{ __('Questions about a campaign, partnerships or your donation? Our team replies within one working day.') }}
            </x-section-heading>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <a href="mailto:{{ $company['email'] }}" class="group card flex gap-4 p-6 transition hover:ring-2 hover:ring-brand">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-mint-50 text-brand"><x-icon name="mail" class="size-6" /></span>
                    <span>
                        <span class="block text-sm text-ink/60">{{ __('Email') }}</span>
                        <span class="block font-semibold text-night group-hover:text-brand">{{ $company['email'] }}</span>
                        <span class="block text-sm text-ink/70">{{ $company['support_email'] }}</span>
                    </span>
                </a>
                <a href="tel:{{ preg_replace('/\s/', '', $company['phone']) }}" class="group card flex gap-4 p-6 transition hover:ring-2 hover:ring-brand">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-mint-50 text-brand"><x-icon name="phone" class="size-6" /></span>
                    <span>
                        <span class="block text-sm text-ink/60">{{ __('Phone') }}</span>
                        <span class="block font-semibold text-night group-hover:text-brand">{{ $company['phone'] }}</span>
                        <span class="block text-sm text-ink/70">{{ __($company['hours']) }}</span>
                    </span>
                </a>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $company['whatsapp']) }}" target="_blank" rel="noopener noreferrer" class="group card flex gap-4 p-6 transition hover:ring-2 hover:ring-brand sm:col-span-2 lg:col-span-1">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-mint-50 text-brand"><x-icon name="whatsapp" class="size-6" /></span>
                    <span>
                        <span class="block text-sm text-ink/60">WhatsApp</span>
                        <span class="block font-semibold text-night group-hover:text-brand">{{ $company['whatsapp'] }}</span>
                        <span class="block text-sm text-ink/70">{{ __('Chat with our donor care team') }}</span>
                    </span>
                </a>
            </div>

            <a href="{{ route('faq') }}" class="group mt-6 flex flex-col items-start gap-3 rounded-2xl bg-mint-50 p-5 ring-1 ring-brand/15 transition hover:ring-brand sm:flex-row sm:items-center">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-white text-brand"><x-icon name="sparkles" class="size-5" /></span>
                <span class="flex-1">
                    <span class="block font-semibold text-night">{{ __('Looking for a quick answer?') }}</span>
                    <span class="block text-sm text-ink/70">{{ __('Refunds, payment methods, anonymous giving, zakat and more are covered in our FAQ.') }}</span>
                </span>
                <span class="flex items-center gap-1 text-sm font-semibold text-brand">{{ __('Read the FAQ') }} <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" /></span>
            </a>

            <div class="mt-10 flex flex-col items-center gap-4">
                <p class="text-sm font-medium text-ink/70">{{ __('Follow our journey') }}</p>
                <div class="flex flex-wrap justify-center gap-3">
                    @foreach (['instagram' => ['instagram', 'Instagram'], 'x' => ['x-social', 'X'], 'facebook' => ['facebook', 'Facebook'], 'youtube' => ['youtube', 'YouTube'], 'tiktok' => ['tiktok', 'TikTok']] as $network => [$icon, $label])
                        <a href="{{ $company['socials'][$network] }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 rounded-full border border-mist px-4 py-2 text-sm text-ink transition hover:border-brand hover:bg-brand hover:text-white">
                            <x-icon :name="$icon" class="size-4" /> {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
