<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** مدیریت مقاله‌های مجله (ایجاد، ویرایش، انتشار، حذف) */
class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles', ['items' => Article::latest('id')->paginate(30)]);
    }

    public function create()
    {
        return view('admin.article-form', ['a' => new Article(['status' => 'draft', 'category' => 'راهنمای رزرو'])]);
    }

    public function edit(Article $article)
    {
        return view('admin.article-form', ['a' => $article]);
    }

    private function save(Request $r, Article $a)
    {
        $d = $r->validate([
            'title' => 'required|string|min:8|max:160', 'meta_title' => 'nullable|string|max:70', 'excerpt' => 'required|string|min:40|max:320',
            'keyword' => 'nullable|string|max:80', 'category' => 'required|string|max:40', 'body' => 'required|string|min:200|max:200000',
            'slug' => ['nullable', 'regex:/^[a-z0-9\-]{3,100}$/'], 'image' => 'nullable|image|max:4096', 'image_alt' => 'nullable|string|max:160', 'status' => 'required|in:draft,published',
        ], ['slug.regex' => 'آدرس (slug) فقط حروف انگلیسی کوچک، عدد و خط تیره باشد (مثلاً ejare-villa).']);
        $slug = $d['slug'] ?: ($a->slug ?: Str::slug(Str::ascii($d['keyword'] ?: $d['title'])) ?: 'article-'.time());
        $exists = Article::where('slug', $slug)->where('id', '!=', $a->id ?? 0)->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['slug' => 'این آدرس قبلاً برای مقاله‌ی دیگری استفاده شده است.']);
        }
        $a->fill([
            'slug' => $slug, 'title' => $d['title'], 'meta_title' => $d['meta_title'] ?? null, 'excerpt' => $d['excerpt'], 'keyword' => $d['keyword'] ?? null,
            'category' => $d['category'], 'body' => Article::clean($d['body']), 'image_alt' => $d['image_alt'] ?? null, 'status' => $d['status'],
        ]);
        if ($r->hasFile('image')) {
            $a->image = $r->file('image')->store('blog', 'public');
        }
        if ($d['status'] === 'published' && ! $a->published_at) {
            $a->published_at = now();
        }
        $a->save();
        Audit::log('article.save', $a->slug);

        return redirect()->route('admin.articles')->with('ok', 'مقاله ذخیره شد'.($a->status === 'published' ? ' و در مجله نمایش داده می‌شود.' : ' (پیش‌نویس).'));
    }

    public function store(Request $r)
    {
        return $this->save($r, new Article);
    }

    public function update(Request $r, Article $article)
    {
        return $this->save($r, $article);
    }

    public function destroy(Article $article)
    {
        Audit::log('article.delete', $article->slug);
        $article->delete();

        return redirect()->route('admin.articles')->with('ok', 'مقاله حذف شد.');
    }
}
