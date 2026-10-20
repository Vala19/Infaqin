@props(['eyebrow' => null, 'title', 'align' => 'center', 'dark' => false])

<div {{ $attributes->merge(['class' => ($align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl')]) }}>
    @if ($eyebrow)
        <p class="eyebrow {{ $dark ? 'text-mint' : '' }}">{{ $eyebrow }}</p>
    @endif
    <h2 class="section-title mt-2 {{ $dark ? 'text-white' : '' }}">{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <p class="mt-3 text-base leading-relaxed {{ $dark ? 'text-white/75' : 'text-ink/80' }}">{{ $slot }}</p>
    @endif
</div>
