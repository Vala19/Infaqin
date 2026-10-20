<x-layouts.app :title="__('News')">
    <section class="bg-forest-900 text-white">
        <div class="container-page py-12">
            <p class="eyebrow text-mint">{{ __('News') }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('What is happening around the world') }}</h1>
            <p class="mt-3 max-w-2xl text-white/70">{{ __('Updates on humanitarian situations, our partners and the people your kindness reaches.') }}</p>
        </div>
    </section>

    <div class="container-page py-12">
        @if ($news->isEmpty())
            <p class="text-center text-ink/60">{{ __('No news has been published yet.') }}</p>
        @else
            <div class="grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($news as $article)
                    <x-news-card :article="$article" />
                @endforeach
            </div>
            <div class="mt-10">{{ $news->links() }}</div>
        @endif
    </div>
</x-layouts.app>
