<x-layouts.app :title="__('Profile')">
    <section class="bg-forest-900 text-white">
        <div class="container-page flex flex-col items-start gap-6 py-10 sm:flex-row sm:items-center">
            <span class="grid size-20 shrink-0 place-items-center rounded-3xl bg-brand text-2xl font-bold shadow-glow">{{ $user->initials() }}</span>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-3xl font-bold tracking-tight">{{ $user->name }}</h1>
                <p class="text-sm text-white/70">{{ '@'.$user->username }} · {{ __('Member since :date', ['date' => $user->created_at->translatedFormat('F Y')]) }}</p>
            </div>
            <dl class="grid w-full grid-cols-2 gap-3 sm:w-auto sm:grid-cols-4">
                @foreach ([[__('Given'), rupiah_short($stats['total'])], [__('Donations'), $stats['count']], [__('Prayers'), $stats['prayers']], [__('Streak'), __(':count wk', ['count' => $stats['streak']])]] as [$label, $value])
                    <div class="rounded-xl bg-white/5 px-4 py-2.5 text-center ring-1 ring-white/10">
                        <dt class="text-[11px] text-white/60">{{ $label }}</dt>
                        <dd class="font-bold">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <div class="container-page grid max-w-5xl gap-8 py-10 lg:grid-cols-[220px_1fr]">
        <nav class="hidden lg:block" aria-label="{{ __('Profile sections') }}">
            <ul class="sticky top-24 space-y-1 text-sm">
                <li><a href="#details" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink hover:bg-white hover:text-brand"><x-figma-icon name="edit" class="size-4" /> {{ __('Personal details') }}</a></li>
                <li><a href="#email" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink hover:bg-white hover:text-brand"><x-icon name="mail" class="size-4 text-brand" /> {{ __('Email & verification') }}</a></li>
                <li><a href="#password" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink hover:bg-white hover:text-brand"><x-icon name="lock" class="size-4 text-brand" /> {{ __('Password') }}</a></li>
                <li><a href="{{ route('history') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink hover:bg-white hover:text-brand"><x-figma-icon name="history" class="size-4" /> {{ __('Donation history') }}</a></li>
            </ul>
        </nav>

        <div class="space-y-8">
            {{-- Personal details --}}
            <section id="details" class="scroll-mt-24 card p-6 sm:p-8" aria-labelledby="details-title">
                <h2 id="details-title" class="flex items-center gap-2 text-lg font-semibold text-night"><x-figma-icon name="edit" class="size-5" /> {{ __('Personal details') }}</h2>
                <p class="mt-1 text-sm text-ink/70">{{ __('Update how your name appears on Infaqin.') }}</p>

                <form method="POST" action="{{ route('profile.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <x-field name="name" :label="__('Full name')" :value="$user->name" required maxlength="100" autocomplete="name" />
                    <x-field name="username" :label="__('Username')" :value="$user->username" required maxlength="30" autocomplete="username" :hint="__('Letters, numbers, dashes and underscores.')" />
                    <x-field name="date_of_birth" :label="__('Date of birth')" type="date" :value="$user->date_of_birth?->toDateString()" max="{{ now()->subDay()->toDateString() }}" />
                    <x-field name="phone" :label="__('Phone number')" type="tel" :value="$user->phone" maxlength="20" autocomplete="tel" placeholder="+62 812 3456 7890" />
                    <div class="flex justify-end sm:col-span-2">
                        <button class="btn-primary">{{ __('Save changes') }}</button>
                    </div>
                </form>
            </section>

            {{-- Email --}}
            <section id="email" class="scroll-mt-24 card p-6 sm:p-8" aria-labelledby="email-title">
                <h2 id="email-title" class="flex items-center gap-2 text-lg font-semibold text-night"><x-icon name="mail" class="size-5 text-brand" /> {{ __('Email & verification') }}</h2>
                <p class="mt-1 text-sm text-ink/70">{{ __('Your sign-in email. Contact support to change it.') }}</p>

                <div class="mt-6 flex flex-col gap-4 rounded-xl border border-mist p-4 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-ink/60">{{ __('Email address') }}</p>
                        <p class="truncate font-medium text-night">{{ $user->email }}</p>
                    </div>
                    @if ($user->hasVerifiedEmail())
                        <span class="chip bg-mint-50 text-brand-700 ring-1 ring-brand/30"><x-icon name="shield-check" class="size-4" /> {{ __('Verified :date', ['date' => $user->email_verified_at->translatedFormat('d M Y')]) }}</span>
                    @else
                        <span class="chip bg-amber-50 text-amber-700 ring-1 ring-amber-300"><x-icon name="alert" class="size-4" /> {{ __('Not verified') }}</span>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button class="btn-primary h-10 w-full sm:w-auto">{{ __('Send verification link') }}</button>
                        </form>
                    @endif
                </div>
                @unless ($user->hasVerifiedEmail())
                    <p class="mt-3 text-xs text-ink/60">{{ __('We will email you a secure link that expires in 60 minutes.') }}</p>
                @endunless
            </section>

            {{-- Password --}}
            <section id="password" class="scroll-mt-24 card p-6 sm:p-8" aria-labelledby="password-title">
                <h2 id="password-title" class="flex items-center gap-2 text-lg font-semibold text-night"><x-icon name="lock" class="size-5 text-brand" /> {{ __('Change password') }}</h2>
                <p class="mt-1 text-sm text-ink/70">{{ __('Use at least 8 characters with upper & lower case letters, a number and a symbol.') }}</p>

                <form method="POST" action="{{ route('profile.password') }}" class="mt-6 grid gap-5 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-2 sm:max-w-sm">
                        <x-field name="current_password" :label="__('Current password')" type="password" bag="password" required autocomplete="current-password" />
                    </div>
                    <x-field name="password" id="new-password" :label="__('New password')" type="password" bag="password" required autocomplete="new-password" />
                    <x-field name="password_confirmation" :label="__('Confirm new password')" type="password" bag="password" required autocomplete="new-password" />
                    <div class="flex justify-end sm:col-span-2">
                        <button class="btn-primary">{{ __('Update password') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>
