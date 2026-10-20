@php
    $campaign = $donation->campaign;
    $isVa = str_ends_with($donation->payment_method, '_va');
    $vaNumber = '8808'.str_pad((string) (crc32($donation->reference) % 10_000_000_000), 10, '0', STR_PAD_LEFT);
@endphp

<x-layouts.app :title="__('Donation :reference', ['reference' => $donation->reference])">
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
                    @if ($isVa)
                        <div x-data="{ copied: false }">
                            <p class="text-sm text-ink/70">{{ __(':method number', ['method' => $donation->paymentMethodLabel()]) }}</p>
                            <div class="mt-2 flex items-center justify-between gap-3 rounded-xl bg-paper px-4 py-3">
                                <span class="font-mono text-xl font-semibold tracking-wider text-night">{{ chunk_split($vaNumber, 4, ' ') }}</span>
                                <button type="button" @click="navigator.clipboard.writeText('{{ $vaNumber }}'); copied = true; setTimeout(() => copied = false, 2000)" class="btn-ghost px-3 text-brand">
                                    <x-icon name="copy" class="size-4" /> <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="text-center">
                            <p class="text-sm text-ink/70">{{ __('Scan with :method or any QRIS-enabled app', ['method' => $donation->paymentMethodLabel()]) }}</p>
                            {{-- Placeholder QR: replace with the payment gateway's QR string. --}}
                            <div class="mx-auto mt-4 grid size-48 place-items-center rounded-2xl border-2 border-dashed border-brand/40 bg-mint-50">
                                <x-logo variant="icon" class="h-16 opacity-80" />
                                <span class="text-xs text-ink/60">{{ __('QR placeholder') }}</span>
                            </div>
                        </div>
                    @endif

                    <ol class="list-decimal space-y-1.5 pl-5 text-sm text-ink/80">
                        <li>{{ $isVa ? __('Open your mobile banking app and choose transfer to virtual account.') : __('Open your e-wallet app and scan the QR code.') }}</li>
                        <li>{!! __('Make sure the amount is exactly :amount.', ['amount' => '<strong>'.e(rupiah($donation->amount)).'</strong>']) !!}</li>
                        <li>{{ __('Confirm the payment. Your donation is recorded instantly.') }}</li>
                    </ol>

                    <div class="rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800 ring-1 ring-amber-200">
                        {{ __('Demo mode: no real payment gateway is connected. Use the button below to simulate a successful payment.') }}
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <form method="POST" action="{{ route('donations.confirm', $donation) }}" class="flex-1" x-data="{ busy: false }" @submit="busy = true">
                            @csrf
                            <button class="btn-primary h-12 w-full" :disabled="busy"><x-icon name="check" class="size-5" /> {{ __('I have paid') }}</button>
                        </form>
                        <form method="POST" action="{{ route('donations.cancel', $donation) }}" onsubmit="return confirm({{ Js::from(__('Cancel this donation?')) }})">
                            @csrf
                            <button class="btn-ghost h-12 w-full text-red-600 hover:bg-red-50">{{ __('Cancel') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="card overflow-hidden">
                <div @class(['px-6 py-10 text-center text-white', 'bg-brand' => $donation->status === 'paid', 'bg-ink' => $donation->status !== 'paid'])>
                    <span class="mx-auto grid size-16 place-items-center rounded-full bg-white/20">
                        <x-icon :name="$donation->status === 'paid' ? 'check' : 'x'" class="size-9" />
                    </span>
                    <h1 class="mt-4 text-2xl font-bold">{{ $donation->status === 'paid' ? 'Jazakallahu khairan!' : __('Donation cancelled') }}</h1>
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
