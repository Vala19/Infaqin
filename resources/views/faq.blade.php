@php
    $faq = collect(config('infaqin.faq'))->map(fn ($group) => [
        'label' => __($group['label']),
        'items' => array_map(fn ($item) => [__($item[0]), __($item[1])], $group['items']),
    ])->all();
    $company = config('infaqin.company');
    $total = collect($faq)->sum(fn ($group) => count($group['items']));
@endphp

<x-layouts.app :title="__('Frequently asked questions')" :description="__('Answers to common questions about donating, payments, transparency, zakat and your Infaqin account.')">
    @push('head')
        {{-- Structured data so search engines can show answers directly. --}}
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faq)->flatMap(fn ($group) => $group['items'])->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
            ])->values(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush

    <div x-data="{
            query: '',
            category: 'all',
            open: null,
            texts: @js(collect($faq)->map(fn ($g) => collect($g['items'])->map(fn ($item) => Str::lower($item[0].' '.$item[1]))->all())),
            matches(group, index) {
                const q = this.query.trim().toLowerCase();
                return (this.category === 'all' || this.category === group) && (!q || this.texts[group][index].includes(q));
            },
            groupVisible(group) {
                return this.texts[group].some((_, i) => this.matches(group, i));
            },
            get anyVisible() {
                return Object.keys(this.texts).some((group) => this.groupVisible(group));
            },
        }">
        <section class="bg-forest-900 text-white">
            <div class="container-page max-w-3xl py-14 text-center">
                <p class="eyebrow text-mint">{{ __('Help centre') }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Frequently asked questions') }}</h1>
                <p class="mx-auto mt-3 max-w-xl text-white/70">{{ __('Everything you need to know about giving with Infaqin. Can’t find your answer? Our team is happy to help.') }}</p>

                <div class="relative mx-auto mt-8 max-w-xl">
                    <label for="faq-search" class="sr-only">{{ __('Search questions') }}</label>
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-ink/50" />
                    <input id="faq-search" type="search" x-model.debounce.150ms="query" @input="open = null" placeholder="{{ __('Search :count questions…', ['count' => $total]) }}" autocomplete="off"
                           class="input h-12 pl-12 text-base">
                </div>
            </div>
        </section>

        <div class="container-page max-w-4xl py-10">
            {{-- Category filter --}}
            <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:justify-center sm:px-0" role="tablist" aria-label="{{ __('FAQ categories') }}">
                @foreach (['all' => __('All')] + collect($faq)->map(fn ($g) => $g['label'])->all() as $key => $label)
                    <button type="button" role="tab" @click="category = '{{ $key }}'; open = null" :aria-selected="category === '{{ $key }}'"
                            class="shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition"
                            :class="category === '{{ $key }}' ? 'border-brand bg-brand text-white' : 'border-mist bg-white text-ink hover:border-brand hover:text-brand'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Questions --}}
            <div class="mt-8 space-y-10">
                @foreach ($faq as $groupKey => $group)
                    <section x-show="groupVisible('{{ $groupKey }}')"
                             aria-labelledby="faq-{{ $groupKey }}">
                        <h2 id="faq-{{ $groupKey }}" class="mb-4 text-lg font-semibold text-night">{{ $group['label'] }}</h2>
                        <div class="space-y-3">
                            @foreach ($group['items'] as $i => [$question, $answer])
                                @php($id = $groupKey.'-'.$i)
                                <div x-show="matches('{{ $groupKey }}', {{ $i }})" class="card overflow-hidden transition"
                                     :class="open === '{{ $id }}' && 'ring-2 ring-brand'">
                                    <h3>
                                        <button type="button" @click="open = open === '{{ $id }}' ? null : '{{ $id }}'"
                                                :aria-expanded="open === '{{ $id }}'" aria-controls="answer-{{ $id }}"
                                                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-medium text-night hover:text-brand">
                                            <span>{{ $question }}</span>
                                            <span class="grid size-7 shrink-0 place-items-center rounded-full transition" :class="open === '{{ $id }}' ? 'rotate-45 bg-brand text-white' : 'bg-mint-50 text-brand'">
                                                <x-icon name="plus" class="size-4" />
                                            </span>
                                        </button>
                                    </h3>
                                    <div id="answer-{{ $id }}" x-show="open === '{{ $id }}'" x-collapse x-cloak>
                                        <p class="px-5 pb-5 text-sm leading-relaxed text-ink/80">{{ $answer }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div x-cloak x-show="!anyVisible" class="card px-6 py-12 text-center">
                    <x-icon name="search" class="mx-auto size-10 text-mist" />
                    <p class="mt-3 font-semibold text-night">{{ __('No questions match') }} “<span x-text="query"></span>”</p>
                    <button type="button" @click="query = ''; category = 'all'" class="btn-outline mt-5">{{ __('Show all questions') }}</button>
                </div>
            </div>

            {{-- Still need help --}}
            <div class="mt-14 flex flex-col items-center gap-5 rounded-3xl bg-forest p-8 text-center text-white sm:flex-row sm:text-left">
                <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/10"><x-icon name="mail" class="size-7 text-mint" /></span>
                <div class="flex-1">
                    <p class="text-lg font-semibold">{{ __('Still have a question?') }}</p>
                    <p class="text-sm text-white/75">{{ __('Our donor care team replies within one working day') }} · {{ __($company['hours']) }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap justify-center gap-2">
                    <a href="mailto:{{ $company['support_email'] }}" class="btn-primary">{{ __('Email us') }}</a>
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $company['whatsapp']) }}" target="_blank" rel="noopener noreferrer" class="btn border border-white/40 text-white hover:bg-white/10">WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
