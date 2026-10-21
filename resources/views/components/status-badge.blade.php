@props(['status'])

@php
    $styles = [
        'paid' => 'bg-mint-50 text-brand-700 ring-brand/30',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-300',
        'expired' => 'bg-paper text-ink/70 ring-mist',
        'failed' => 'bg-red-50 text-red-700 ring-red-200',
        'draft' => 'bg-amber-50 text-amber-700 ring-amber-300',
        'active' => 'bg-mint-50 text-brand-700 ring-brand/30',
        'closed' => 'bg-paper text-ink ring-mist',
    ];
    $labels = array_map(fn ($label) => __($label), \App\Models\Donation::STATUS_LABELS);
@endphp

<span {{ $attributes->merge(['class' => 'chip ring-1 '.($styles[$status] ?? $styles['closed'])]) }}>{{ $labels[$status] ?? __(ucfirst($status)) }}</span>
