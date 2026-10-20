<x-layouts.admin title="News">
    <div class="flex flex-col justify-between gap-3 sm:flex-row">
        <form method="GET" class="relative w-full sm:max-w-sm" role="search">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-ink/50" />
            <input type="search" name="q" value="{{ $term }}" placeholder="Search news…" class="input h-11 pl-11" aria-label="Search news">
        </form>
        <a href="{{ route('admin.news.create') }}" class="btn-primary h-11"><x-icon name="plus" class="size-4" /> Write news</a>
    </div>

    <div class="mt-6 overflow-x-auto card">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-paper text-xs tracking-wide text-ink/60 uppercase">
                <tr>
                    <th class="px-5 py-3 font-semibold">Article</th>
                    <th class="px-5 py-3 font-semibold">Category</th>
                    <th class="px-5 py-3 font-semibold">Published</th>
                    <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/60">
                @forelse ($news as $article)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $article->imageUrl() }}" alt="" class="size-11 rounded-lg object-cover">
                                <span class="max-w-md truncate font-medium text-night">{{ $article->title }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-ink/80">{{ $article->category }}</td>
                        <td class="px-5 py-3 whitespace-nowrap text-ink/80">
                            @if (! $article->published_at) <span class="chip bg-paper text-ink ring-1 ring-mist">Draft</span>
                            @elseif ($article->published_at->isFuture()) <span class="chip bg-amber-50 text-amber-700 ring-1 ring-amber-300">Scheduled {{ $article->published_at->format('d M Y') }}</span>
                            @else {{ $article->published_at->format('d M Y, H:i') }} @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-1">
                                @if ($article->published_at?->isPast())
                                    <a href="{{ route('news.show', $article) }}" target="_blank" class="grid size-9 place-items-center rounded-lg text-ink/60 hover:bg-paper hover:text-brand" aria-label="View"><x-icon name="external" class="size-4" /></a>
                                @endif
                                <a href="{{ route('admin.news.edit', $article) }}" class="grid size-9 place-items-center rounded-lg text-ink/60 hover:bg-paper hover:text-brand" aria-label="Edit"><x-icon name="pencil" class="size-4" /></a>
                                <form method="POST" action="{{ route('admin.news.destroy', $article) }}" onsubmit="return confirm('Delete this article permanently?')">
                                    @csrf @method('DELETE')
                                    <button class="grid size-9 place-items-center rounded-lg text-ink/60 hover:bg-red-50 hover:text-red-600" aria-label="Delete"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-ink/60">No news found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $news->links() }}</div>
</x-layouts.admin>
