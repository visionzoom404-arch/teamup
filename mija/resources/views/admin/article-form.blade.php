@extends('layouts.panel')
@section('title', $a->exists ? 'ویرایش مقاله' : 'مقاله‌ی جدید')
@section('content')
<div class="page-h"><div><a href="{{ route('admin.articles') }}" class="small muted">← مقاله‌ها</a><h1>{{ $a->exists ? 'ویرایش مقاله' : 'مقاله‌ی جدید' }}</h1></div></div>
@include('partials.help', ['title' => 'چک‌لیست سئوی مقاله', 'text' => 'کلمه‌ی کلیدی اصلی را در عنوان، جمله‌ی اول، یکی دو زیرعنوان (h2) و توضیح بنویسید ولی زیاد تکرار نکنید (حدود ۱ تا ۲ درصد). طول مناسب: ۸۰۰ تا ۲۰۰۰ کلمه. چند لینک به صفحه‌های جستجو (مثل /search?region=north) و مقاله‌های دیگر بگذارید. عکس را با توضیح (alt) بارگذاری کنید. زیرعنوان‌ها را با <h2> و <h3> بنویسید؛ فهرست مطالب خودکار ساخته می‌شود.'])
<form class="card" method="post" enctype="multipart/form-data" action="{{ $a->exists ? route('admin.articles.update', $a) : route('admin.articles.store') }}">@csrf
  <div class="grid2">
    <div class="field"><label>عنوان مقاله (H1)</label><input class="input" name="title" value="{{ old('title', $a->title) }}" maxlength="160" required>@error('title')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field"><label>کلمه‌ی کلیدی اصلی</label><input class="input" name="keyword" value="{{ old('keyword', $a->keyword) }}" placeholder="مثلاً اجاره ویلا" maxlength="80"></div>
    <div class="field"><label>عنوان سئو (حداکثر ۶۰ کاراکتر؛ اختیاری)</label><input class="input" name="meta_title" value="{{ old('meta_title', $a->meta_title) }}" maxlength="70"><div class="help">اگر خالی باشد از عنوان مقاله استفاده می‌شود. «| می‌جا» خودکار اضافه می‌شود.</div></div>
    <div class="field"><label>آدرس (slug انگلیسی)</label><input class="input" dir="ltr" name="slug" value="{{ old('slug', $a->slug) }}" placeholder="ejare-villa">@error('slug')<div class="msg-e">{{ $message }}</div>@enderror<div class="help">آدرس نهایی: {{ url('/blog') }}/<b>slug</b> — بعد از انتشار تغییرش ندهید.</div></div>
    <div class="field"><label>دسته</label><input class="input" name="category" value="{{ old('category', $a->category) }}" maxlength="40" required></div>
    <div class="field"><label>وضعیت</label><select class="input" name="status"><option value="draft" @selected(old('status', $a->status) === 'draft')>پیش‌نویس</option><option value="published" @selected(old('status', $a->status) === 'published')>منتشر شود</option></select></div>
  </div>
  <div class="field"><label>خلاصه / توضیح متا (۱۲۰ تا ۱۶۰ کاراکتر مناسب است)</label><textarea class="input" name="excerpt" rows="3" maxlength="320" required>{{ old('excerpt', $a->excerpt) }}</textarea>@error('excerpt')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="grid2">
    <div class="field"><label>عکس شاخص (۱۶:۹ مثل ۱۲۰۰×۶۷۵)</label><input class="input" type="file" name="image" accept="image/*">@if($a->image)<img src="{{ $a->imageUrl() }}" alt="" style="max-width:220px;border-radius:12px;margin-top:8px">@endif @error('image')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field"><label>متن جایگزین عکس (alt)</label><input class="input" name="image_alt" value="{{ old('image_alt', $a->image_alt) }}" maxlength="160"></div>
  </div>
  <div class="field"><label>متن مقاله (HTML ساده: p, h2, h3, ul, ol, li, a, strong, table)</label><textarea class="input" name="body" rows="22" dir="rtl" style="font-family:monospace;line-height:1.8" required>{{ old('body', $a->body) }}</textarea>@error('body')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="row wrap" style="gap:8px"><button class="btn">ذخیره</button><a class="btn btn-light" href="{{ route('admin.articles') }}">انصراف</a></div>
</form>
@endsection
