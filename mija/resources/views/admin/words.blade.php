@extends('layouts.panel')
@section('title', 'کلمات ممنوع')
@section('content')
<div class="page-h"><h1>کلمات ممنوع</h1></div>
@include('partials.help', ['title' => 'چطور کار می‌کند؟', 'text' => 'اگر در نظر، پیام یا توضیح کاربر یکی از این کلمات باشد، سیستم خطای «استفاده از این کلمه مجاز نیست» می‌دهد و متن ثبت نمی‌شود. چند کلمه‌ی رکیک به‌صورت پیش‌فرض داخل سیستم است و شما می‌توانید کلمات دلخواه خود را اضافه کنید.', 'open' => true])
<form method="post" action="{{ route('admin.words.store') }}" class="card mb">@csrf<h3>افزودن کلمه</h3><div class="field"><textarea class="input" name="words" rows="3" placeholder="هر کلمه در یک خط یا با ویرگول جدا شود" required></textarea>@error('words')<div class="msg-e">{{ $message }}</div>@enderror</div><button class="btn">افزودن</button></form>
<div class="card mb"><h3>کلمات شما ({{ fa($items->count()) }})</h3><div class="chips">@forelse($items as $w)<form method="post" action="{{ route('admin.words.destroy', $w) }}" style="display:inline">@csrf @method('DELETE')<button class="badge red" style="border:0;cursor:pointer" title="حذف">{{ $w->word }} ✕</button></form>@empty<span class="muted small">هنوز کلمه‌ای اضافه نکرده‌اید.</span>@endforelse</div></div>
<div class="card"><h3>کلمات پیش‌فرض سیستم</h3><div class="chips">@foreach($defaults as $w)<span class="badge gray">{{ $w }}</span>@endforeach</div></div>
@endsection
