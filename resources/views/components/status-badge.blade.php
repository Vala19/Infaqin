@props(['status'])

@php
    $styles = [
        'paid' => 'bg-mint-50 text-brand-700 ring-brand/30',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-300',
        'failed' => 'bg-red-50 text-red-700 ring-red-200',
        'active' => 'bg-mint-50 text-brand-700 ring-brand/30',
        'completed' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'closed' => 'bg-paper text-ink ring-mist',
    ];
    $labels = ['paid' => __('Success'), 'pending' => __('Waiting payment'), 'failed' => __('Cancelled')];
@endphp

<span {{ $attributes->merge(['class' => 'chip ring-1 '.($styles[$status] ?? $styles['closed'])]) }}>{{ $labels[$status] ?? __(ucfirst($status)) }}</span>
