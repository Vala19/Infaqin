@php
    $withdrawnTotal = $campaign->withdrawals->sum('amount');
    $canDonate = $campaign->isOpen();
    $donateUrl = route('donations.create', $campaign);
@endphp

<x-layouts.app :title="$campaign->title" :description="$campaign->summary">
    @push('head')
        <meta property="og:title" content="{{ $campaign->title }}">
        <meta property="og:description" content="{{ $campaign->summary }}">
        <meta property="og:image" content="{{ $campaign->imageUrl() }}">
    @endpush

    <div class="container-page py-8 lg:py-12">
        <nav class="flex items-center gap-1.5 text-sm text-ink/60" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('campaigns.index') }}" class="hover:text-brand">{{ __('Campaigns') }}</a>
            <x-icon name="chevron-right" class="size-4" />
            <a href="{{ route('campaigns.index', ['category' => [$campaign->category?->slug]]) }}" class="hover:text-brand">{{ $campaign->categoryLabel() }}</a>
            <x-icon name="chevron-right" class="size-4" />
            <span class="truncate text-ink">{{ $campaign->title }}</span>
        </nav>

        <div class="mt-6 grid gap-10 lg:grid-cols-[1fr_400px]">
            {{-- ================= MAIN COLUMN ================= --}}
            <div class="min-w-0">
                <div class="relative overflow-hidden rounded-3xl bg-mist shadow-card">
                    <img src="{{ $campaign->imageUrl() }}" alt="{{ $campaign->title }}" class="aspect-[361/310] w-full object-cover sm:aspect-[16/10]">
                    <div class="absolute top-4 left-4 flex gap-2">
                        <span class="chip {{ $campaign->isProgram() ? 'bg-forest text-white' : 'bg-white/95 text-forest' }}" data-campaign-type="hero">{{ $campaign->typeLabel() }}</span>
                        <span class="chip bg-white/95 text-forest">{{ $campaign->categoryLabel() }}</span>
                        @if ($campaign->is_urgent) <span class="chip bg-red-600 text-white">{{ __('Urgent') }}</span> @endif
                    </div>
                </div>

                <h1 class="mt-8 text-3xl leading-tight font-bold tracking-tight text-night sm:text-4xl">{{ $campaign->title }}</h1>
                <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink/70">
                    @if ($campaign->location) <span class="flex items-center gap-1"><x-icon name="map-pin" class="size-4" /> {{ $campaign->location }}</span> @endif
                    <span class="flex items-center gap-1"><x-icon name="calendar" class="size-4" /> {{ __('Started :date', ['date' => $campaign->created_at->translatedFormat('d M Y')]) }}</span>
                </p>

                {{-- Mobile donation box --}}
                <div class="mt-6 lg:hidden">
                    @include('campaigns.partials.donate-box')
                </div>

                {{-- Description --}}
                <section class="mt-10" x-data="{ more: false }" aria-labelledby="story-title">
                    <h2 id="story-title" class="text-xl font-semibold text-night">{{ __('The story') }}</h2>
                    <div class="relative mt-4">
                        <div class="prose-infaqin overflow-hidden transition-all" :class="more ? '' : 'max-h-56'">
                            @foreach (preg_split('/\n\s*\n/', $campaign->description) as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                        <div x-show="!more" class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-linear-to-t from-paper"></div>
                    </div>
                    <button type="button" @click="more = !more" class="mt-2 flex items-center gap-1 text-sm font-semibold text-brand hover:underline" :aria-expanded="more">
                        <span x-text="more ? @js(__('Show less')) : @js(__('Read the full story'))">{{ __('Read the full story') }}</span>
                        <x-icon name="chevron-down" class="size-4 transition" ::class="more && 'rotate-180'" />
                    </button>
                </section>

                {{-- Open Fund Ledger --}}
                <section class="mt-12" aria-labelledby="ledger-title">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <h2 id="ledger-title" class="flex items-center gap-2 text-xl font-semibold text-night"><x-figma-icon name="withdraw" class="size-6" /> {{ __('Open Fund Ledger') }}</h2>
                            <p class="mt-1 text-sm text-ink/70">{!! __('Funds have been cashed out :times, totalling :total.', [
                                'times' => '<strong class="text-night">'.e(trans_choice(':count time|:count times', $campaign->withdrawals->count())).'</strong>',
                                'total' => e(rupiah($withdrawnTotal)),
                            ]) !!}</p>
                        </div>
                    </div>
                    @if ($campaign->withdrawals->isEmpty())
                        <p class="mt-4 rounded-xl border border-dashed border-mist p-5 text-sm text-ink/60">{{ __('No withdrawals yet. Every cash-out will appear here with its purpose.') }}</p>
                    @else
                        <ol class="relative mt-6 space-y-5 border-l-2 border-mint pl-6">
                            @foreach ($campaign->withdrawals as $w)
                                <li class="relative">
                                    <span class="absolute top-1.5 -left-[31px] size-3.5 rounded-full border-2 border-white bg-brand ring-2 ring-mint"></span>
                                    <div class="card flex flex-col justify-between gap-2 p-4 sm:flex-row sm:items-center">
                                        <div>
                                            <p class="text-sm font-medium text-night">{{ $w->description }}</p>
                                            <p class="text-xs text-ink/60">{{ $w->withdrawn_at->translatedFormat('d M Y, H:i') }}</p>
                                        </div>
                                        <p class="shrink-0 font-semibold text-forest">{{ rupiah($w->amount) }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                {{-- Prayers / comments --}}
                <section id="prayers" class="mt-12 scroll-mt-24" x-data="{ expanded: false }" aria-labelledby="prayers-title">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="prayers-title" class="flex items-center gap-2 text-xl font-semibold text-night">
                            <x-figma-icon name="care" class="size-6" /> {{ __('Prayers') }} <span class="text-base font-normal text-ink/60">({{ $commentCount }})</span>
                        </h2>
                        @if ($commentCount > 0)
                            <button type="button" @click="expanded = !expanded" class="flex items-center gap-1 text-sm font-semibold text-brand" :aria-expanded="expanded" aria-controls="prayer-list">
                                <span x-text="expanded ? @js(__('Collapse')) : @js(__('Expand'))">{{ __('Expand') }}</span>
                                <x-icon name="chevron-down" class="size-4 transition" ::class="expanded && 'rotate-180'" />
                            </button>
                        @endif
                    </div>

                    @auth
                        @if ($eligibleDonation)
                            <form method="POST" action="{{ route('comments.store', $campaign) }}" class="mt-5 card p-5" x-data="{ body: @js(old('body', '')) }">
                                @csrf
                                <label for="body" class="label">{{ __('Leave a prayer for the people you helped') }}</label>
                                <textarea id="body" name="body" rows="3" maxlength="1000" required x-model="body"
                                          class="input resize-y @error('body') input-error @enderror" placeholder="{{ __('May Allah make it easy…') }}"></textarea>
                                @error('body') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-xs text-ink/50" x-text="body.length + '/1000'"></span>
                                    <button class="btn-primary">{{ __('Share prayer') }}</button>
                                </div>
                            </form>
                        @else
                            <p class="mt-4 rounded-xl bg-mint-50 px-4 py-3 text-sm text-forest">
                                <x-icon name="heart" class="mr-1 inline size-4" /> {{ __('Prayers can be shared after you donate to this campaign.') }}
                            </p>
                        @endif
                    @else
                        <p class="mt-4 rounded-xl bg-mint-50 px-4 py-3 text-sm text-forest">
                            {!! __(':login and donate to share your prayer.', ['login' => '<a href="'.route('login').'" class="font-semibold underline">'.e(__('Log in')).'</a>']) !!}
                        </p>
                    @endauth

                    @if ($comments->isEmpty())
                        <p class="mt-6 text-sm text-ink/60">{{ __('No prayers yet.') }}</p>
                    @else
                        <div id="prayer-list" x-data="loadMore(@js($comments->nextPageUrl() ? route('campaigns.comments', ['campaign' => $campaign, 'page' => 2]) : null))">
                            <div x-ref="list" class="mt-6 grid gap-4 overflow-hidden transition-all duration-500"
                                 :class="expanded ? '' : 'max-h-[270px] [mask-image:linear-gradient(to_bottom,black_70%,transparent)]'">
                                @foreach ($comments as $comment)
                                    <x-prayer-card :comment="$comment" :amened="in_array($comment->id, $amenedIds)" />
                                @endforeach
                            </div>
                            <div class="mt-4 flex justify-center gap-3">
                                <button type="button" x-show="!expanded" @click="expanded = true" class="btn-outline">{{ __('Show all prayers') }}</button>
                                <button type="button" x-cloak x-show="expanded && nextUrl" @click="load()" :disabled="loading" class="btn-outline">
                                    <span x-text="loading ? @js(__('Loading…')) : @js(__('Load more prayers'))">{{ __('Load more prayers') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </section>
            </div>

            {{-- ================= SIDEBAR ================= --}}
            <aside class="space-y-6">
                <div class="hidden lg:sticky lg:top-24 lg:block">
                    @include('campaigns.partials.donate-box')

                    {{-- Campaigner profile --}}
                    <div class="mt-6 card p-6">
                        <p class="text-xs font-semibold tracking-wide text-ink/60 uppercase">{{ __('Campaigner') }}</p>
                        @include('campaigns.partials.campaigner')
                    </div>
                </div>

                <div class="card p-6 lg:hidden">
                    <p class="text-xs font-semibold tracking-wide text-ink/60 uppercase">{{ __('Campaigner') }}</p>
                    @include('campaigns.partials.campaigner')
                </div>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-20">
                <h2 class="text-2xl font-bold text-night">{{ __('More :category campaigns', ['category' => Str::lower($campaign->categoryLabel())]) }}</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-campaign-card :campaign="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
