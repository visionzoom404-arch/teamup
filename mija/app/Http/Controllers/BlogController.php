<?php

namespace App\Http\Controllers;

use App\Models\Article;

/** مجله‌ی می‌جا (بلاگ) برای سئو */
class BlogController extends Controller
{
    public function index()
    {
        return view('blog.index', ['items' => Article::published()->latest('published_at')->paginate(12)]);
    }

    public function show(Article $article)
    {
        abort_unless($article->status === 'published' && (! $article->published_at || $article->published_at->lte(now())) || auth()->user()?->isAdmin(), 404);
        if (! auth()->user()?->isAdmin()) {
            $article->increment('views');
        }
        [$html, $toc] = $article->rendered();

        return view('blog.show', ['a' => $article, 'html' => $html, 'toc' => $toc, 'related' => $article->related(3)]);
    }
}
