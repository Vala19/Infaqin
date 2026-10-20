@props(['title', 'subtitle', 'image' => 'images/campaigns/team.jpg'])

<x-layouts.app :title="$title" nav="auth" :hide-footer="true">
    <div class="grid min-h-[calc(100vh-72px)] lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden bg-forest lg:block">
            <img src="{{ asset($image) }}" alt="" class="absolute inset-0 size-full object-cover opacity-50">
            <div class="absolute inset-0 bg-linear-to-t from-forest-900 via-forest-900/70 to-forest/30"></div>
            <div class="relative flex h-full flex-col justify-end p-12 text-white">
                <x-logo variant="tile-wordmark" class="h-24 rounded-2xl shadow-xl" />
                <p class="mt-8 max-w-md text-3xl leading-snug font-semibold">“{{ __('Charity does not decrease wealth.') }}”</p>
                <p class="mt-2 text-sm text-mint">— Sahih Muslim</p>
                <ul class="mt-10 space-y-3 text-sm text-white/80">
                    <li class="flex items-center gap-3"><x-icon name="shield-check" class="size-5 text-mint" /> {{ __('Verified campaigners only') }}</li>
                    <li class="flex items-center gap-3"><x-icon name="receipt" class="size-5 text-mint" /> {{ __('Every withdrawal published on the ledger') }}</li>
                    <li class="flex items-center gap-3"><x-icon name="lock" class="size-5 text-mint" /> {{ __('Your data is encrypted and never sold') }}</li>
                </ul>
            </div>
        </aside>

        <div class="flex items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-md">
                <x-logo variant="horizontal" class="h-11 lg:hidden" />
                <h1 class="mt-6 text-3xl font-bold tracking-tight text-night lg:mt-0">{{ $title }}</h1>
                <p class="mt-2 text-sm text-ink/70">{{ $subtitle }}</p>
                <div class="mt-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
