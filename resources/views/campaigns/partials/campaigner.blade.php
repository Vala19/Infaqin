@php($campaigner = $campaign->campaigner)

<div class="mt-4 flex items-center gap-3">
    <img src="{{ $campaigner->avatarUrl() }}" alt="" class="size-14 rounded-2xl object-cover">
    <div class="min-w-0">
        <p class="flex items-center gap-1.5 font-semibold text-night">
            <span class="truncate">{{ $campaigner->name }}</span>
            @if ($campaigner->is_verified)
                <x-icon name="shield-check" class="size-4 shrink-0 text-brand" />
            @endif
        </p>
        <p class="text-xs text-ink/60">{{ $campaigner->is_verified ? __('Verified campaigner') : __('Campaigner') }} · {{ $campaigner->location }}</p>
    </div>
</div>
@if ($campaigner->bio)
    <p class="mt-4 text-sm leading-relaxed text-ink/80">{{ $campaigner->bio }}</p>
@endif
<dl class="mt-4 grid grid-cols-2 gap-2 text-center text-sm">
    <div class="rounded-xl bg-paper p-3">
        <dt class="text-[11px] text-ink/60">{{ __('Campaigns') }}</dt>
        <dd class="font-bold text-night">{{ $campaigner->campaigns_count }}</dd>
    </div>
    <div class="rounded-xl bg-paper p-3">
        <dt class="text-[11px] text-ink/60">{{ __('Joined') }}</dt>
        <dd class="font-bold text-night">{{ $campaigner->created_at->translatedFormat('M Y') }}</dd>
    </div>
</dl>
