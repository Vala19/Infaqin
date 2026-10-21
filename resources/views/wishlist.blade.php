<x-layouts.app :title="__('Wishlist')">
    <section class="bg-forest-900 text-white">
        <div class="container-page flex items-center gap-4 py-10">
            <span class="grid size-14 place-items-center rounded-2xl bg-white text-red-500"><x-icon name="heart" class="size-8 fill-current" /></span>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">{{ __('Wishlist') }}</h1>
                <p class="text-sm text-white/70">{{ __('Campaigns you saved to come back to and support later.') }}</p>
            </div>
        </div>
    </section>

    <div class="container-page py-10">
        @if ($campaigns->isEmpty())
            <div class="card flex flex-col items-center px-6 py-16 text-center">
                <x-icon name="heart" class="size-12 text-mist" />
                <h2 class="mt-4 text-lg font-semibold text-night">{{ __('Your wishlist is empty') }}</h2>
                <p class="mt-1 text-sm text-ink/70">{{ __('Tap the heart on any campaign to save it here.') }}</p>
                <a href="{{ route('campaigns.index') }}" class="btn-primary mt-6">{{ __('Find a campaign') }}</a>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($campaigns as $campaign)
                    {{-- Removing a campaign here hides its card right away. --}}
                    <div x-data="{ removed: false }" x-show="!removed" x-transition.opacity @wishlist-changed="removed = ! $event.detail.saved" class="flex">
                        <x-campaign-card :campaign="$campaign" :saved="true" :popover="false" class="w-full">
                            @if ($campaign->isOpen())
                                <a href="{{ route('donations.create', $campaign) }}" class="btn-primary mt-4 w-full">
                                    <x-icon name="heart" class="size-4" /> {{ __('Donate') }}
                                </a>
                            @else
                                <span class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-mist py-2.5 text-sm font-semibold text-ink/70" data-campaign-closed>{{ __('Closed') }}</span>
                            @endif
                        </x-campaign-card>
                    </div>
                @endforeach
            </div>
            <div class="mt-10">{{ $campaigns->links() }}</div>
        @endif
    </div>
</x-layouts.app>
