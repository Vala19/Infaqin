@props(['variant' => 'icon', 'class' => 'h-10'])

@php
    $src = [
        'icon' => 'images/logo/icon.png',
        'horizontal' => 'images/logo/logo-horizontal.png',
        'wordmark' => 'images/logo/wordmark-green.png',
        'tile' => 'images/logo/icon-green-tile.png',
        'tile-wordmark' => 'images/logo/icon-green-tile-wordmark.png',
        'tile-dark' => 'images/logo/icon-dark-tile.png',
    ][$variant];
@endphp

<img src="{{ asset($src) }}" alt="Infaqin" {{ $attributes->merge(['class' => $class.' w-auto object-contain']) }}>
