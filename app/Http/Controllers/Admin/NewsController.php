<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->limit(100)->toString();

        $news = News::query()
            ->when($term, fn ($query) => $query->where('title', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.news.index', compact('news', 'term'));
    }

    public function create(): View
    {
        return view('admin.news.form', ['article' => new News(['published_at' => now(), 'category' => 'World'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $article = new News($data);
        $article->slug = $this->uniqueSlug($data['title']);
        $article->image = $this->storeImage($request);
        $article->author()->associate($request->user());
        $article->save();

        return redirect()->route('admin.news.index')->with('status', 'News "'.$article->title.'" published.');
    }

    public function edit(News $news): View
    {
        return view('admin.news.form', ['article' => $news]);
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['title'] !== $news->title) {
            $news->slug = $this->uniqueSlug($data['title'], $news->id);
        }

        if ($image = $this->storeImage($request)) {
            $this->deleteImage($news->image);
            $news->image = $image;
        }

        $news->fill($data)->save();

        return redirect()->route('admin.news.index')->with('status', 'News "'.$news->title.'" updated.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->deleteImage($news->image);
        $news->delete();

        return redirect()->route('admin.news.index')->with('status', 'News deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(News::CATEGORIES)],
            'excerpt' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:20000'],
            'published_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset($data['image']);

        return array_map(fn ($value) => is_string($value) ? strip_tags($value) : $value, $data);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return 'storage/'.$request->file('image')->store('news', 'public');
    }

    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'news';
        $slug = $base;
        $i = 2;

        while (News::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
