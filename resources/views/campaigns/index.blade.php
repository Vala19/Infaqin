@php
    $selectedCategories = $filters['category'] ?? [];
    $activeFilterCount = count($selectedCategories) + (($filters['status'] ?? 'active') !== 'active' ? 1 : 0) + (empty($filters['type']) ? 0 : 1) + (request()->boolean('urgent') ? 1 : 0);
@endphp

<x-layouts.app :title="__('Campaigns')">
    <section class="bg-forest-900 text-white">
        <div class="container-page py-12 lg:py-16">
            <p class="eyebrow text-mint">{{ __('Campaigns') }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Find a cause close to your heart') }}</h1>
            <p class="mt-3 max-w-2xl text-white/70">{{ __('Every campaign is run by a verified campaigner and every withdrawal is published. Hover a card for a quick look.') }}</p>

            <form method="GET" action="{{ route('campaigns.index') }}" role="search" class="mt-8 flex max-w-2xl gap-2">
                @foreach ($selectedCategories as $cat)
                    <input type="hidden" name="category[]" value="{{ $cat }}">
                @endforeach
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                @if (! empty($filters['type'])) <input type="hidden" name="type" value="{{ $filters['type'] }}"> @endif
                @if (request()->boolean('urgent')) <input type="hidden" name="urgent" value="1"> @endif
                <label for="campaign-search" class="sr-only">{{ __('Search campaigns') }}</label>
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-ink/50" />
                    <input id="campaign-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100"
                           placeholder="{{ __('Search by title, place or campaigner…') }}" class="input h-12 pl-12 text-base">
                </div>
                <button class="btn-primary h-12 px-6">{{ __('Search') }}</button>
            </form>
        </div>
    </section>

    <div class="container-page py-10" x-data="{ filtersOpen: false }">
        <div class="grid gap-8 lg:grid-cols-[260px_1fr]">
            {{-- ================= FILTERS ================= --}}
            <aside>
                <button type="button" @click="filtersOpen = !filtersOpen" class="btn-outline w-full lg:hidden" :aria-expanded="filtersOpen">
                    <x-figma-icon name="filter" class="size-5" /> {{ __('Filters') }} @if ($activeFilterCount) ({{ $activeFilterCount }}) @endif
                </button>

                <form method="GET" action="{{ route('campaigns.index') }}" x-ref="filters"
                      class="mt-4 space-y-6 card p-5 lg:sticky lg:top-24 lg:mt-0 lg:block" :class="filtersOpen ? 'block' : 'hidden'">
                    <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                    <input type="hidden" name="sort" value="{{ $filters['sort'] }}">

                    <div class="flex items-center justify-between">
                        <h2 class="flex items-center gap-2 font-semibold text-night"><x-figma-icon name="filter" class="size-5" /> {{ __('Filter') }}</h2>
                        @if ($activeFilterCount)
                            <a href="{{ route('campaigns.index', array_filter(['q' => $filters['q'] ?? null, 'sort' => $filters['sort']])) }}" class="text-xs font-medium text-brand hover:underline">{{ __('Reset') }}</a>
                        @endif
                    </div>

                    <fieldset>
                        <legend class="mb-3 text-xs font-semibold tracking-wide text-ink/60 uppercase">{{ __('Category') }}</legend>
                        <div class="space-y-2.5">
                            @foreach ($categories as $category)
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink">
                                    <input type="checkbox" name="category[]" value="{{ $category->slug }}" @checked(in_array($category->slug, $selectedCategories))
                                           @change="$el.form.requestSubmit()" class="size-4 rounded border-mist accent-brand">
                                    {{ __($category->name) }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="mb-3 text-xs font-semibold tracking-wide text-ink/60 uppercase">{{ __('Status') }}</legend>
                        <div class="grid grid-cols-3 gap-1 rounded-lg bg-paper p-1 text-xs font-medium">
                            @foreach (['active' => __('Active'), 'closed' => __('Closed'), 'all' => __('All')] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="{{ $value }}" class="peer sr-only" @checked($filters['status'] === $value) @change="$el.form.requestSubmit()">
                                    <span class="block rounded-md py-1.5 text-center text-ink/70 transition peer-checked:bg-white peer-checked:text-brand peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="mb-3 text-xs font-semibold tracking-wide text-ink/60 uppercase">{{ __('Type') }}</legend>
                        <div class="grid grid-cols-3 gap-1 rounded-lg bg-paper p-1 text-xs font-medium">
                            @foreach (['' => __('All'), 'program' => __('Program'), 'campaign' => __('Campaign')] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="type" value="{{ $value }}" class="peer sr-only" @checked(($filters['type'] ?? '') === $value) @change="$el.form.requestSubmit()">
                                    <span class="block rounded-md py-1.5 text-center text-ink/70 transition peer-checked:bg-white peer-checked:text-brand peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-toggle name="urgent" :label="__('Urgent only')" :checked="request()->boolean('urgent')" @change="$nextTick(() => $el.form.requestSubmit())" />

                    <noscript><button class="btn-primary w-full">{{ __('Apply filters') }}</button></noscript>
                </form>
            </aside>

            {{-- ================= RESULTS ================= --}}
            <div>
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <p class="text-sm text-ink/70">
                        {!! trans_choice(':count campaign|:count campaigns', $campaigns->total(), ['count' => '<span class="font-semibold text-night">'.$campaigns->total().'</span>']) !!}
                        @if (! empty($filters['q'])) {!! __('for “:query”', ['query' => '<span class="font-medium text-night">'.e($filters['q']).'</span>']) !!} @endif
                    </p>

                    <form method="GET" action="{{ route('campaigns.index') }}" class="flex items-center gap-2">
                        @foreach (request()->except(['sort', 'page']) as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $v) <input type="hidden" name="{{ $key }}[]" value="{{ $v }}"> @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="sort" class="flex items-center gap-1.5 text-sm text-ink/70"><x-figma-icon name="sort" class="size-5" /> {{ __('Sort') }}</label>
                        <div class="relative">
                            <select id="sort" name="sort" onchange="this.form.requestSubmit()" class="input h-10 appearance-none py-0 pr-10 shadow-card">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink/60" />
                        </div>
                        <noscript><button class="btn-outline h-10">{{ __('Go') }}</button></noscript>
                    </form>
                </div>

                @if ($campaigns->isEmpty())
                    <div class="mt-10 card flex flex-col items-center px-6 py-16 text-center">
                        <x-icon name="search" class="size-12 text-mist" />
                        <h2 class="mt-4 text-lg font-semibold text-night">{{ __('No campaigns match your search') }}</h2>
                        <p class="mt-1 text-sm text-ink/70">{{ __('Try a different keyword or clear some filters.') }}</p>
                        <a href="{{ route('campaigns.index') }}" class="btn-primary mt-6">{{ __('Clear everything') }}</a>
                    </div>
                @else
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($campaigns as $campaign)
                            <x-campaign-card :campaign="$campaign" :saved="in_array($campaign->id, $wishlistIds)" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $campaigns->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
