@props(['article'])

<article {{ $attributes->merge(['class' => 'group card flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-xl']) }}>
    <a href="{{ route('news.show', $article) }}" class="block aspect-[16/10] overflow-hidden bg-mist">
        <img src="{{ $article->imageUrl() }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2 text-xs text-ink/60">
            <span class="chip bg-mint-50 text-forest">{{ __($article->category) }}</span>
            <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->translatedFormat('d M Y') }}</time>
            <span>· {{ __(':count min read', ['count' => $article->readingMinutes()]) }}</span>
        </div>
        <h3 class="mt-3 line-clamp-2 font-semibold text-night group-hover:text-brand">
            <a href="{{ route('news.show', $article) }}">{{ $article->title }}</a>
        </h3>
        <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink/75">{{ $article->excerpt }}</p>
    </div>
</article>
