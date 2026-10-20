<x-layouts.app :title="$article->title" :description="$article->excerpt">
    <article class="container-page max-w-3xl py-10">
        <a href="{{ route('news.index') }}" class="inline-flex items-center gap-1.5 text-sm text-ink/70 hover:text-brand"><x-icon name="arrow-left" class="size-4" /> {{ __('All news') }}</a>

        <div class="mt-6 flex flex-wrap items-center gap-2 text-sm text-ink/60">
            <span class="chip bg-mint-50 text-forest">{{ __($article->category) }}</span>
            <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->translatedFormat('d F Y') }}</time>
            <span>· {{ __(':count min read', ['count' => $article->readingMinutes()]) }}</span>
            @if ($article->author) <span>· {{ __('by :author', ['author' => $article->author->name]) }}</span> @endif
        </div>
        <h1 class="mt-4 text-3xl leading-tight font-bold tracking-tight text-night sm:text-4xl">{{ $article->title }}</h1>
        <p class="mt-4 text-lg leading-relaxed text-ink/80">{{ $article->excerpt }}</p>

        <img src="{{ $article->imageUrl() }}" alt="" class="mt-8 aspect-[16/9] w-full rounded-3xl object-cover shadow-card">

        <div class="prose-infaqin mt-10 text-[17px]">
            @foreach (preg_split('/\n\s*\n/', $article->body) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center gap-4 rounded-3xl bg-forest p-8 text-center text-white sm:flex-row sm:text-left">
            <x-icon name="hands" class="size-12 shrink-0 text-mint" />
            <div class="flex-1">
                <p class="font-semibold">{{ __('Moved by this story?') }}</p>
                <p class="text-sm text-white/75">{{ __('Turn concern into action — find a campaign helping people right now.') }}</p>
            </div>
            <a href="{{ route('campaigns.index') }}" class="btn-primary shrink-0">{{ __('Help now') }}</a>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="bg-white py-14">
            <div class="container-page">
                <h2 class="text-2xl font-bold text-night">{{ __('More news') }}</h2>
                <div class="mt-6 grid gap-7 md:grid-cols-3">
                    @foreach ($more as $item)
                        <x-news-card :article="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
