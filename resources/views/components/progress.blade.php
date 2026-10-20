@props(['campaign', 'size' => 'md', 'showAmounts' => true, 'dark' => false])

{{-- Target bar from the Figma "Intractable" sheet: filled green segment + outlined remainder. --}}
@php
    $pct = $campaign->progress();
    $height = ['sm' => 'h-2', 'md' => 'h-3', 'lg' => 'h-5'][$size];
@endphp

<div {{ $attributes }}>
    @if ($showAmounts)
        <div class="mb-1.5 flex items-end justify-between gap-2 text-xs">
            <div>
                <span class="block {{ $dark ? 'text-white/60' : 'text-ink/60' }}">{{ __('Collected') }}</span>
                <span class="font-semibold italic {{ $dark ? 'text-white' : 'text-night' }}">{{ rupiah($campaign->collected_amount) }}</span>
            </div>
            <div class="text-right">
                <span class="block {{ $dark ? 'text-white/60' : 'text-ink/60' }}">{{ __('Target') }}</span>
                <span class="font-medium italic {{ $dark ? 'text-white/80' : 'text-ink' }}">{{ rupiah($campaign->target_amount) }}</span>
            </div>
        </div>
    @endif
    <div class="{{ $height }} w-full overflow-hidden rounded-lg border {{ $dark ? 'border-white/40' : 'border-night/70' }}"
         role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __(':percent% funded', ['percent' => $pct]) }}">
        <div class="h-full rounded-l-lg bg-brand transition-[width] duration-700" style="width: {{ max($pct, 2) }}%"></div>
    </div>
</div>
