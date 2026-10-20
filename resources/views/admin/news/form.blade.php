@php($editing = $article->exists)

<x-layouts.admin :title="$editing ? 'Edit news' : 'Write news'">
    <a href="{{ route('admin.news.index') }}" class="inline-flex items-center gap-1.5 text-sm text-ink/70 hover:text-brand"><x-icon name="arrow-left" class="size-4" /> Back to news</a>

    <form method="POST" action="{{ $editing ? route('admin.news.update', $article) : route('admin.news.store') }}" enctype="multipart/form-data"
          class="mt-4 grid gap-6 xl:grid-cols-[1fr_320px]" x-data="{ preview: @js($editing ? $article->imageUrl() : null) }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card space-y-5 p-6">
            <x-field name="title" label="Title" :value="$article->title" required maxlength="200" />
            <div>
                <label for="excerpt" class="label">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="2" maxlength="300" required class="input @error('excerpt') input-error @enderror">{{ old('excerpt', $article->excerpt) }}</textarea>
                @error('excerpt') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="body" class="label">Body</label>
                <textarea id="body" name="body" rows="16" required class="input font-[inherit] leading-relaxed @error('body') input-error @enderror">{{ old('body', $article->body) }}</textarea>
                @error('body') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @else <p class="mt-1.5 text-xs text-ink/60">Plain text. Separate paragraphs with a blank line.</p> @enderror
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <div>
                    <label for="category" class="label">Category</label>
                    <select id="category" name="category" class="input">
                        @foreach (\App\Models\News::CATEGORIES as $category)
                            <option value="{{ $category }}" @selected(old('category', $article->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field name="published_at" label="Publish at" type="datetime-local" :value="$article->published_at?->format('Y-m-d\TH:i')" hint="Leave empty to save as draft. A future date schedules it." />
            </div>

            <div class="card p-6">
                <label for="image" class="label">Cover image</label>
                <div class="mt-2 aspect-[16/10] overflow-hidden rounded-xl bg-paper ring-1 ring-mist">
                    <img x-show="preview" :src="preview" alt="" class="size-full object-cover">
                    <div x-show="!preview" class="grid size-full place-items-center text-xs text-ink/50">No image — placeholder will be used</div>
                </div>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                       @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)"
                       class="mt-3 block w-full text-sm text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-mint-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-forest hover:file:bg-mint/40">
                @error('image') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @else <p class="mt-1.5 text-xs text-ink/60">JPG, PNG or WebP, max 2 MB.</p> @enderror
            </div>

            <button class="btn-primary h-12 w-full">{{ $editing ? 'Save changes' : 'Publish news' }}</button>
        </div>
    </form>
</x-layouts.admin>
