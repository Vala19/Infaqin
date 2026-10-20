@php
    $hasFilters = collect($filters)->except('sort')->filter()->isNotEmpty();
@endphp

<x-layouts.app :title="__('Donation history')">
    <section class="bg-forest-900 text-white">
        <div class="container-page flex flex-col justify-between gap-6 py-10 md:flex-row md:items-end">
            <div class="flex items-center gap-4">
                <span class="grid size-14 place-items-center rounded-2xl bg-white"><x-figma-icon name="history" class="size-9" /></span>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ __('Donation history') }}</h1>
                    <p class="text-sm text-white/70">{{ __('Every donation you have made, with its status and receipt.') }}</p>
                </div>
            </div>
            <dl class="flex gap-3">
                <div class="rounded-xl bg-white/5 px-5 py-3 ring-1 ring-white/10"><dt class="text-xs text-white/60">{{ __('Donations') }}</dt><dd class="text-lg font-bold">{{ $summary['count'] }}</dd></div>
                <div class="rounded-xl bg-white/5 px-5 py-3 ring-1 ring-white/10"><dt class="text-xs text-white/60">{{ __('Successfully given') }}</dt><dd class="text-lg font-bold text-mint">{{ rupiah($summary['paid']) }}</dd></div>
            </dl>
        </div>
    </section>

    <div class="container-page py-8">
        {{-- Search, filter & sort toolbar --}}
        <form method="GET" action="{{ route('history') }}" class="card p-4" x-data="{ more: @js($hasFilters) }">
            <div class="flex flex-col gap-3 md:flex-row">
                <label for="history-q" class="sr-only">{{ __('Search donations') }}</label>
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-ink/50" />
                    <input id="history-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="{{ __('Search campaign, reference or dedication…') }}" class="input h-11 pl-11">
                </div>
                <div class="flex gap-2">
                    <div class="relative flex-1 md:w-48 md:flex-none">
                        <label for="history-sort" class="sr-only">{{ __('Sort') }}</label>
                        <x-figma-icon name="sort" class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2" />
                        <select id="history-sort" name="sort" onchange="this.form.requestSubmit()" class="input h-11 appearance-none pr-9 pl-10">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink/60" />
                    </div>
                    <button type="button" @click="more = !more" class="btn-outline h-11" :aria-expanded="more" aria-controls="history-filters">
                        <x-figma-icon name="filter" class="size-5" /> <span class="hidden sm:inline">{{ __('Filter') }}</span>
                    </button>
                    <button class="btn-primary h-11">{{ __('Search') }}</button>
                </div>
            </div>

            <div id="history-filters" x-show="more" x-collapse @unless ($hasFilters) x-cloak @endunless>
                <div class="mt-4 grid gap-4 border-t border-mist/60 pt-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="f-status" class="label">{{ __('Status') }}</label>
                        <select id="f-status" name="status" class="input h-11">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (['paid' => __('Success'), 'pending' => __('Waiting payment'), 'failed' => __('Cancelled')] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="f-method" class="label">{{ __('Payment method') }}</label>
                        <select id="f-method" name="method" class="input h-11">
                            <option value="">{{ __('All methods') }}</option>
                            @foreach ($methods as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['method'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="f-from" class="label">{{ __('From') }}</label>
                        <input id="f-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" max="{{ now()->toDateString() }}" class="input h-11">
                    </div>
                    <div>
                        <label for="f-to" class="label">{{ __('To') }}</label>
                        <input id="f-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" max="{{ now()->toDateString() }}" class="input h-11 @error('to') input-error @enderror">
                        @error('to') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    @if ($hasFilters)
                        <a href="{{ route('history') }}" class="btn-ghost h-10">{{ __('Clear all') }}</a>
                    @endif
                    <button class="btn-primary h-10">{{ __('Apply filters') }}</button>
                </div>
            </div>
        </form>

        {{-- Results --}}
        @if ($donations->isEmpty())
            <div class="mt-8 card flex flex-col items-center px-6 py-16 text-center">
                <x-figma-icon name="donation" class="size-14 opacity-60" />
                <h2 class="mt-4 text-lg font-semibold text-night">{{ $hasFilters ? __('No donations match these filters') : __('No donations yet') }}</h2>
                <p class="mt-1 text-sm text-ink/70">{{ $hasFilters ? __('Try widening your search.') : __('Your first act of kindness is one click away.') }}</p>
                <a href="{{ $hasFilters ? route('history') : route('campaigns.index') }}" class="btn-primary mt-6">{{ $hasFilters ? __('Clear filters') : __('Find a campaign') }}</a>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="mt-6 hidden overflow-hidden card md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-paper text-xs tracking-wide text-ink/60 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Campaign') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Date') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Method') }}</th>
                            <th scope="col" class="px-5 py-3 text-right font-semibold">{{ __('Amount') }}</th>
                            <th scope="col" class="px-5 py-3 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist/60">
                        @foreach ($donations as $donation)
                            <tr class="transition hover:bg-mint-50/50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $donation->campaign->imageUrl() }}" alt="" class="size-11 shrink-0 rounded-lg object-cover">
                                        <div class="min-w-0">
                                            <p class="max-w-xs truncate font-medium text-night">{{ $donation->campaign->title }}</p>
                                            <p class="text-xs text-ink/55">{{ $donation->reference }} @if ($donation->is_anonymous) · Hamba Allah @endif @if ($donation->on_behalf_of) · {{ __('for :name', ['name' => $donation->on_behalf_of]) }} @endif</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-ink/80">{{ $donation->created_at->translatedFormat('d M Y') }}<span class="block text-xs text-ink/50">{{ $donation->created_at->format('H:i') }}</span></td>
                                <td class="px-5 py-4 whitespace-nowrap text-ink/80">{{ $donation->paymentMethodLabel() }}</td>
                                <td class="px-5 py-4 text-right font-semibold whitespace-nowrap text-night">{{ rupiah($donation->amount) }}</td>
                                <td class="px-5 py-4"><x-status-badge :status="$donation->status" /></td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('donations.show', $donation) }}" class="text-sm font-semibold whitespace-nowrap text-brand hover:underline">
                                        {{ $donation->status === 'pending' ? __('Pay now') : __('Receipt') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <ul class="mt-6 space-y-3 md:hidden">
                @foreach ($donations as $donation)
                    <li>
                        <a href="{{ route('donations.show', $donation) }}" class="card flex gap-3 p-4">
                            <img src="{{ $donation->campaign->imageUrl() }}" alt="" class="size-14 shrink-0 rounded-lg object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-night">{{ $donation->campaign->title }}</p>
                                <p class="text-xs text-ink/55">{{ $donation->created_at->translatedFormat('d M Y, H:i') }} · {{ $donation->paymentMethodLabel() }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="font-semibold text-night">{{ rupiah($donation->amount) }}</span>
                                    <x-status-badge :status="$donation->status" />
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $donations->links() }}</div>
        @endif
    </div>
</x-layouts.app>
