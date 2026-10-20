@props(['comment', 'amened' => false, 'showCampaign' => false])

<article {{ $attributes->merge(['class' => 'card flex flex-col p-5']) }}>
    <header class="flex items-start gap-3">
        <span @class([
            'grid size-10 shrink-0 place-items-center rounded-full text-xs font-bold',
            'bg-forest text-white' => ! $comment->donation?->is_anonymous,
            'bg-mist text-ink' => $comment->donation?->is_anonymous,
        ])>{{ $comment->authorInitials() }}</span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-night">{{ $comment->authorName() }}</p>
            <p class="text-xs text-ink/60">
                {{ __('Donated :amount', ['amount' => rupiah_short($comment->donation?->amount)]) }} · <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time>
            </p>
        </div>
        <x-figma-icon name="care" class="size-6 shrink-0 opacity-70" />
    </header>

    <p class="mt-4 flex-1 text-sm leading-relaxed text-ink">{{ $comment->body }}</p>

    @if ($showCampaign && $comment->campaign)
        <a href="{{ route('campaigns.show', $comment->campaign) }}#prayers" class="mt-3 truncate text-xs font-medium text-brand hover:underline">
            {{ __('for :campaign', ['campaign' => $comment->campaign->title]) }}
        </a>
    @endif

    <footer class="mt-4 flex items-center justify-between border-t border-mist/60 pt-3">
        @auth
            <button type="button" x-data="amenButton('{{ route('comments.amen', $comment) }}', {{ $amened ? 'true' : 'false' }}, {{ $comment->amens_count }})"
                    @click="toggle()" :disabled="busy" :aria-pressed="amened"
                    class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition"
                    :class="amened ? 'bg-brand text-white' : 'bg-mint-50 text-forest hover:bg-mint/40'">
                <x-icon name="hands" class="size-4" />
                <span x-text="amened ? 'Aamiin' : @js(__('Say Aamiin'))">{{ $amened ? 'Aamiin' : __('Say Aamiin') }}</span>
                <span class="opacity-75" x-text="'· ' + count">· {{ $comment->amens_count }}</span>
            </button>
        @else
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 rounded-full bg-mint-50 px-3 py-1.5 text-xs font-semibold text-forest hover:bg-mint/40">
                <x-icon name="hands" class="size-4" /> {{ __('Say Aamiin') }} · {{ $comment->amens_count }}
            </a>
        @endauth
        <span class="text-[11px] text-ink/50">{{ trans_choice(':count person said Aamiin|:count people said Aamiin', $comment->amens_count) }}</span>
    </footer>
</article>
