@php
    $user = auth()->user();
    $initialAmount = old('amount', $prefillAmount);
@endphp

<x-layouts.app :title="__('Donate')">
    <div class="container-page max-w-5xl py-10">
        <a href="{{ route('campaigns.show', $campaign) }}" class="inline-flex items-center gap-1.5 text-sm text-ink/70 hover:text-brand"><x-icon name="arrow-left" class="size-4" /> {{ __('Back to campaign') }}</a>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-night">{{ __('Make your donation') }}</h1>

        <form method="POST" action="{{ route('donations.store', $campaign) }}" class="mt-8 grid gap-8 lg:grid-cols-[1fr_340px]"
              x-data="{
                  amount: @js($initialAmount ? (int) preg_replace('/\D/', '', $initialAmount) : null),
                  anonymous: @js((bool) old('is_anonymous')),
                  busy: false,
                  get display() { return this.amount ? Number(this.amount).toLocaleString('id-ID') : '' },
                  setFromInput(value) { const n = String(value).replace(/\D/g, ''); this.amount = n ? Math.min(Number(n), {{ \App\Models\Donation::MAX_AMOUNT }}) : null },
                  get valid() { return this.amount >= {{ \App\Models\Donation::MIN_AMOUNT }} },
              }"
              @submit="busy = true">
            @csrf

            <div class="space-y-6">
                {{-- Amount --}}
                <section class="card p-6" aria-labelledby="amount-title">
                    <h2 id="amount-title" class="font-semibold text-night">{{ __('1. Choose an amount') }}</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($presets as $preset)
                            <button type="button" @click="amount = {{ $preset }}"
                                    class="rounded-xl border-2 px-3 py-3 text-sm font-semibold transition"
                                    :class="amount === {{ $preset }} ? 'border-brand bg-mint-50 text-brand' : 'border-mist text-night hover:border-brand/50'"
                                    :aria-pressed="amount === {{ $preset }}">
                                {{ rupiah($preset) }}
                            </button>
                        @endforeach
                    </div>

                    <label for="amount-input" class="label mt-5">{{ __('Or enter another amount') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-4 grid place-items-center font-semibold text-ink/60">Rp</span>
                        <input id="amount-input" type="text" inputmode="numeric" autocomplete="off" placeholder="0"
                               :value="display" @input="setFromInput($event.target.value); $nextTick(() => $event.target.value = display)"
                               class="input h-14 pl-12 text-xl font-semibold @error('amount') input-error @enderror">
                        <input type="hidden" name="amount" :value="amount ?? ''">
                    </div>
                    @error('amount')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-ink/60">{{ __('Minimum :amount', ['amount' => rupiah(\App\Models\Donation::MIN_AMOUNT)]) }}</p>
                    @enderror
                </section>

                {{-- Details --}}
                <section class="card space-y-5 p-6" aria-labelledby="details-title">
                    <h2 id="details-title" class="font-semibold text-night">{{ __('2. Your details') }}</h2>
                    <x-field name="email" :label="__('Email for your receipt')" type="email" :value="$user->email" required autocomplete="email" />
                    <x-field name="on_behalf_of" :label="__('Donate on behalf of (optional)')" maxlength="100" :placeholder="__('e.g. My late grandmother, Siti Aminah')"
                             :hint="__('Their name will appear on your receipt.')" />

                    <div class="rounded-xl border border-mist p-4">
                        <x-toggle name="is_anonymous" :label="__('Donate anonymously as “Hamba Allah”')" :checked="(bool) old('is_anonymous')" @change="anonymous = $event.target.checked" />
                        <p class="mt-2 text-xs text-ink/60">
                            {{ __('Your name will be hidden from donor lists and prayers. Shown publicly as') }}
                            <strong class="text-night" x-text="anonymous ? 'Hamba Allah' : @js($user->name)">{{ $user->name }}</strong>.
                        </p>
                    </div>
                </section>
            </div>

            {{-- Summary --}}
            <aside>
                <div class="card overflow-hidden lg:sticky lg:top-24">
                    <img src="{{ $campaign->imageUrl() }}" alt="" class="aspect-[16/9] w-full object-cover">
                    <div class="p-6">
                        <p class="text-xs text-ink/60">{{ __('You are donating to') }}</p>
                        <p class="mt-1 font-semibold text-night">{{ $campaign->title }}</p>
                        <p class="mt-1 flex items-center gap-1 text-xs text-ink/60">{{ $campaign->campaigner->name }} <x-icon name="shield-check" class="size-3.5 text-brand" /></p>

                        <dl class="mt-5 space-y-2 border-t border-mist/60 pt-4 text-sm">
                            <div class="flex justify-between"><dt class="text-ink/70">{{ __('Donation') }}</dt><dd class="font-medium" x-text="amount ? 'Rp' + display : '—'">—</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink/70">{{ __('Method') }}</dt><dd class="text-right text-ink/70">{{ __('Chosen in the payment window') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink/70">{{ __('Platform fee') }}</dt><dd class="font-medium text-brand">{{ __('Free') }}</dd></div>
                            <div class="flex justify-between border-t border-mist/60 pt-3 text-base"><dt class="font-semibold">{{ __('Total') }}</dt><dd class="font-bold text-brand" x-text="amount ? 'Rp' + display : 'Rp0'">Rp0</dd></div>
                        </dl>

                        <button type="submit" class="btn-primary mt-6 h-12 w-full text-base" :disabled="!valid || busy">
                            <x-icon name="lock" class="size-4" />
                            <span x-text="busy ? @js(__('Processing…')) : @js(__('Continue to payment'))">{{ __('Continue to payment') }}</span>
                        </button>
                        <p class="mt-3 text-center text-[11px] leading-relaxed text-ink/50">
                            {!! __('Secured with encrypted checkout. By continuing you agree to our :terms and :refunds.', [
                                'terms' => '<a href="'.route('terms').'#donations" target="_blank" class="text-brand hover:underline">'.e(__('donation terms')).'</a>',
                                'refunds' => '<a href="'.route('terms').'#refunds" target="_blank" class="text-brand hover:underline">'.e(__('refund policy')).'</a>',
                            ]) !!}
                        </p>
                    </div>
                </div>
            </aside>
        </form>
    </div>
</x-layouts.app>
