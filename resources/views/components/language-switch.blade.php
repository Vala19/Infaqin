{{-- Language picker for the navbar: shows the current language code and opens a small menu. --}}
@php($current = app()->getLocale())

<div {{ $attributes->merge(['class' => 'relative']) }} x-data="{ langOpen: false }" @click.outside="langOpen = false" @keydown.escape="langOpen = false">
    <button type="button" @click="langOpen = !langOpen" :aria-expanded="langOpen" aria-haspopup="true"
            aria-label="{{ __('Language') }}: {{ config('infaqin.locales')[$current] }}"
            class="flex h-9 items-center gap-1.5 rounded-lg px-2.5 text-sm font-medium text-white transition hover:bg-white/10">
        <x-icon name="globe" class="size-4.5" />
        <span class="uppercase">{{ $current }}</span>
        <x-icon name="chevron-down" class="size-3.5 transition" ::class="langOpen && 'rotate-180'" />
    </button>
    <div x-cloak x-show="langOpen" x-transition.origin.top.right
         class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl bg-white py-1.5 text-sm shadow-xl ring-1 ring-black/5">
        <p class="px-4 pt-1 pb-2 text-[11px] font-semibold tracking-wide text-ink/50 uppercase">{{ __('Language') }}</p>
        @foreach (config('infaqin.locales') as $code => $name)
            <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" @if ($code === $current) aria-current="true" @endif
               @class(['flex items-center gap-3 px-4 py-2 hover:bg-mint-50', 'font-semibold text-brand' => $code === $current, 'text-ink' => $code !== $current])>
                <span class="grid h-6 w-8 place-items-center rounded-md bg-paper text-[11px] font-bold uppercase">{{ $code }}</span>
                <span class="flex-1">{{ $name }}</span>
                @if ($code === $current) <x-icon name="check" class="size-4" /> @endif
            </a>
        @endforeach
    </div>
</div>
