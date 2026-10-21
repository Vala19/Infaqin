@php
    $campaign = $donation->campaign;
    $heading = match ($donation->status) {
        'paid' => 'Jazakallahu khairan!',
        'expired' => __('Payment expired'),
        default => __('Donation cancelled / payment failed'),
    };
@endphp

<x-layouts.app :title="__('Donation :reference', ['reference' => $donation->reference])">
    @if ($donation->status === 'pending' && $donation->snap_token)
        @push('head')
            <script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>
        @endpush
    @endif

    <div class="container-page max-w-2xl py-12">
        @if ($donation->status === 'pending')
            <div class="card overflow-hidden">
                <div class="bg-forest px-6 py-8 text-center text-white">
                    <x-icon name="clock" class="mx-auto size-12 text-mint" />
                    <h1 class="mt-3 text-2xl font-bold">{{ __('Complete your payment') }}</h1>
                    <p class="mt-1 text-sm text-white/70">{{ __('Reference :reference', ['reference' => $donation->reference]) }}</p>
                    <p class="mt-4 text-4xl font-bold">{{ rupiah($donation->amount) }}</p>
                    <p class="mt-2 text-xs text-white/70">{{ __('Pay before :date', ['date' => $donation->expiresAt()->translatedFormat('d M Y, H:i')]) }}</p>
                </div>

                <div class="space-y-6 p-6 sm:p-8">
                    @if ($donation->snap_token)
                        <div x-data="snapPayment(@js($donation->snap_token), @js(route('donations.status', $donation)), @js((bool) session('open_snap')))" class="space-y-4">
                            <ol class="list-decimal space-y-1.5 pl-5 text-sm text-ink/80">
                                <li>{{ __('Press "Pay now" and choose QRIS, e-wallet, virtual account or another method in the Midtrans window.') }}</li>
                                <li>{{ __('Follow the instructions in that window to complete the payment.') }}</li>
                                <li>{{ __('Your status updates automatically once Midtrans confirms the payment.') }}</li>
                            </ol>

                            <button type="button" @click="pay()" class="btn-primary h-12 w-full"><x-icon name="lock" class="size-5" /> {{ __('Pay now') }}</button>

                            <p x-cloak x-show="polling" class="flex items-center justify-center gap-2 text-sm text-ink/70" role="status">
                                <x-icon name="clock" class="size-4" /> {{ __('Waiting for confirmation from Midtrans…') }}
                            </p>
                            <div x-cloak x-show="timedOut" class="rounded-xl bg-paper px-4 py-3 text-center text-sm text-ink/80">
                                <p>{{ __('No confirmation yet. If you have paid, it can take a few minutes.') }}</p>
                                <button type="button" @click="window.location.reload()" class="btn-outline mt-3 h-10 px-5">{{ __('Refresh status') }}</button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                            {{ __('This donation has no payment session. Cancel it and make a new donation.') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('donations.cancel', $donation) }}" onsubmit="return confirm({{ Js::from(__('Cancel this donation?')) }})">
                        @csrf
                        <button class="btn-ghost h-12 w-full text-red-600 hover:bg-red-50">{{ __('Cancel donation') }}</button>
                    </form>
                </div>
            </div>
        @else
            <div class="card overflow-hidden">
                <div @class(['px-6 py-10 text-center text-white', 'bg-brand' => $donation->status === 'paid', 'bg-ink' => $donation->status !== 'paid'])>
                    <span class="mx-auto grid size-16 place-items-center rounded-full bg-white/20">
                        <x-icon :name="$donation->status === 'paid' ? 'check' : 'x'" class="size-9" />
                    </span>
                    <h1 class="mt-4 text-2xl font-bold">{{ $heading }}</h1>
                    <p class="mt-1 text-white/85">
                        {{ $donation->status === 'paid' ? __('Your kindness is on its way to :campaign.', ['campaign' => $campaign->title]) : __('No money was taken for this donation.') }}
                    </p>
                </div>

                <dl class="divide-y divide-mist/60 px-6 text-sm sm:px-8">
                    @foreach (array_filter([
                        __('Reference') => $donation->reference,
                        __('Campaign') => $campaign->title,
                        __('Amount') => rupiah($donation->amount),
                        __('Payment method') => $donation->paymentMethodLabel(),
                        __('Shown publicly as') => $donation->publicName(),
                        __('On behalf of') => $donation->on_behalf_of,
                        __('Receipt sent to') => $donation->email,
                        __('Date') => ($donation->paid_at ?? $donation->created_at)->translatedFormat('d M Y, H:i'),
                    ]) as $label => $value)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-ink/60">{{ $label }}</dt>
                            <dd class="text-right font-medium text-night">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="space-y-3 p-6 sm:p-8">
                    @if ($donation->status === 'paid' && ! $donation->comment)
                        <a href="{{ route('campaigns.show', $campaign) }}#prayers" class="btn-primary h-12 w-full"><x-figma-icon name="care" class="size-5 brightness-0 invert" /> {{ __('Leave a prayer') }}</a>
                    @endif
                    <div class="grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('history') }}" class="btn-outline h-11">{{ __('View history') }}</a>
                        <a href="{{ route('campaigns.index') }}" class="btn-ghost h-11 border border-mist">{{ __('Explore more') }}</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
