@props(['name', 'class' => 'size-6', 'alt' => ''])

{{-- Brand icons exported from the Infaqin Figma asset sheet (public/images/icons). --}}
<img src="{{ asset('images/icons/'.$name.'.svg') }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => $class.' object-contain']) }} @if ($alt === '') aria-hidden="true" @endif>
