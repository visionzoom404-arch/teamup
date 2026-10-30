@extends('layouts.panel')
@section('title', 'ویرایش '.$page->title)
@section('content')
<div class="page-h"><div><a href="{{ route('admin.pages') }}" class="small muted">← صفحه‌ها</a><h1>ویرایش: {{ $page->title }}</h1></div><a class="btn btn-sm btn-light" target="_blank" href="{{ route('page', $page->slug) }}">مشاهده</a></div>
<form method="post" action="{{ route('admin.pages.update', $page->slug) }}" class="card">@csrf
  <div class="field"><label>عنوان</label><input class="input" name="title" value="{{ old('title', $page->title) }}" required>@error('title')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="field"><label>متن (HTML ساده: &lt;h3&gt;، &lt;p&gt;، &lt;ul&gt;، &lt;li&gt;، &lt;b&gt;)</label><textarea class="input" name="body" rows="22" dir="rtl" style="font-family:monospace;font-size:13px;line-height:1.9" required>{{ old('body', $page->body) }}</textarea>@error('body')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <button class="btn btn-lg">ذخیره</button>
</form>
@if($custom)<form method="post" action="{{ route('admin.pages.destroy', $page->slug) }}" class="mt" onsubmit="return confirm('متن این صفحه به حالت پیش‌فرض برگردد؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost">بازگشت به متن پیش‌فرض</button></form>@endif
@endsection
