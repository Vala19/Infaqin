<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return view('news.index', [
            'news' => News::published()->latest('published_at')->paginate(9),
        ]);
    }

    public function show(News $news): View
    {
        abort_unless($news->published_at && $news->published_at->isPast(), 404);

        $news->load('author');

        return view('news.show', [
            'article' => $news,
            'more' => News::published()->whereKeyNot($news->id)->latest('published_at')->take(3)->get(),
        ]);
    }
}
