@php($company = config('infaqin.company'))

<footer class="bg-night text-white/70">
    <div class="container-page grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
        <div>
            <div class="flex items-center gap-3">
                <x-logo variant="icon" class="h-12" />
                <x-logo variant="wordmark" class="h-9" />
            </div>
            <p class="mt-4 max-w-xs text-sm leading-relaxed">{{ __($company['tagline']) }} {{ __('A transparent home for infaq, sedekah and zakat — for people in need, everywhere.') }}</p>
            <div class="mt-5 flex gap-2">
                @foreach (['instagram', 'x' => 'x-social', 'facebook', 'youtube', 'tiktok'] as $key => $icon)
                    @php($network = is_int($key) ? $icon : $key)
                    <a href="{{ $company['socials'][$network] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Infaqin on :network', ['network' => ucfirst($network)]) }}"
                       class="grid size-9 place-items-center rounded-lg bg-white/5 text-white/80 transition hover:bg-brand hover:text-white">
                        <x-icon :name="$icon" class="size-4" />
                    </a>
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold tracking-wide text-white">{{ __('Explore') }}</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('campaigns.index') }}" class="hover:text-mint">{{ __('All campaigns') }}</a></li>
                <li><a href="{{ route('campaigns.index', ['urgent' => 1]) }}" class="hover:text-mint">{{ __('Urgent needs') }}</a></li>
                <li><a href="{{ route('news.index') }}" class="hover:text-mint">{{ __('News') }}</a></li>
                <li><a href="{{ route('landing') }}#features" class="hover:text-mint">{{ __('Zakat calculator') }}</a></li>
                <li><a href="{{ route('faq') }}" class="hover:text-mint">{{ __('FAQ') }}</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold tracking-wide text-white">{{ __('Account') }}</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                @auth
                    <li><a href="{{ route('home') }}" class="hover:text-mint">{{ __('Home') }}</a></li>
                    <li><a href="{{ route('profile.edit') }}" class="hover:text-mint">{{ __('Profile') }}</a></li>
                    <li><a href="{{ route('history') }}" class="hover:text-mint">{{ __('Donation history') }}</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-mint">{{ __('Log in') }}</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-mint">{{ __('Create account') }}</a></li>
                @endauth
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold tracking-wide text-white">{{ __('Our office') }}</h3>
            <address class="mt-4 space-y-2.5 text-sm not-italic">
                <p class="flex gap-2.5"><x-icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-brand" /> {{ $company['address'] }}</p>
                <p class="flex gap-2.5"><x-icon name="mail" class="mt-0.5 size-4 shrink-0 text-brand" /> <a href="mailto:{{ $company['email'] }}" class="hover:text-mint">{{ $company['email'] }}</a></p>
                <p class="flex gap-2.5"><x-icon name="phone" class="mt-0.5 size-4 shrink-0 text-brand" /> <a href="tel:{{ preg_replace('/\s/', '', $company['phone']) }}" class="hover:text-mint">{{ $company['phone'] }}</a></p>
            </address>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-page flex flex-col items-center justify-between gap-2 py-5 text-xs text-white/50 sm:flex-row">
            <p>&copy; {{ now()->year }} {{ $company['legal_name'] }}. {{ __('All rights reserved.') }}</p>
            <nav class="flex gap-5" aria-label="{{ __('Legal') }}">
                <a href="{{ route('terms') }}" class="hover:text-mint">{{ __('Terms & Conditions') }}</a>
                <a href="{{ route('terms') }}#privacy" class="hover:text-mint">{{ __('Privacy') }}</a>
                <a href="{{ route('faq') }}" class="hover:text-mint">{{ __('FAQ') }}</a>
            </nav>
        </div>
    </div>
</footer>
