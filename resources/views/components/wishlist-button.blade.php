@props(['campaign', 'saved' => false])

{{-- Heart that saves a campaign to the wishlist. Guests are sent to log in and brought back to the campaign. --}}
@php
    $buttonClass = 'grid size-10 place-items-center rounded-full bg-white/95 text-red-500 shadow-sm ring-1 ring-black/5 transition hover:scale-105 focus-visible:outline-2 focus-visible:outline-brand';
@endphp

@auth
    <form method="POST" action="{{ route('wishlist.update', $campaign) }}"
          x-data="wishlistButton(@js(route('wishlist.update', $campaign)), @js((bool) $saved))" @submit.prevent="toggle()"
          {{ $attributes }}>
        @csrf
        <input type="hidden" name="saved" value="{{ $saved ? 0 : 1 }}" :value="saved ? 0 : 1">
        <button type="submit" class="{{ $buttonClass }}" :disabled="busy" data-wishlist-button
                aria-label="{{ __('Save to wishlist') }}" title="{{ __('Save to wishlist') }}"
                aria-pressed="{{ $saved ? 'true' : 'false' }}" :aria-pressed="saved.toString()">
            <x-icon name="heart" :class="'size-5'.($saved ? ' fill-current' : '')" ::class="{ 'fill-current': saved }" />
        </button>
    </form>
@else
    <a href="{{ route('wishlist.campaign', $campaign) }}" class="{{ $buttonClass }}" data-wishlist-button
       aria-label="{{ __('Save to wishlist') }}" title="{{ __('Save to wishlist') }}" {{ $attributes }}>
        <x-icon name="heart" class="size-5" />
    </a>
@endauth
