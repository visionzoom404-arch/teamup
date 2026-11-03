@extends('layouts.panel')
@section('title', 'مقاله‌ها')
@section('content')
<div class="page-h"><div><h1>مقاله‌های مجله</h1><div class="muted">برای سئو: هر مقاله یک کلمه‌ی کلیدی اصلی، عنوان و توضیح مناسب و لینک به صفحه‌های جستجو دارد</div></div><div class="row" style="gap:8px"><a class="btn btn-sm btn-light" target="_blank" href="{{ route('blog') }}">مشاهده‌ی مجله</a><a class="btn btn-sm" href="{{ route('admin.articles.create') }}"><x-icon name="plus" class="sm"/> مقاله‌ی جدید</a></div></div>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>مقاله</th><th>کلمه‌ی کلیدی</th><th>وضعیت</th><th>بازدید</th><th></th></tr></thead><tbody>
@forelse($items as $a)
<tr><td><b>{{ $a->title }}</b><div class="xs muted ltr" style="text-align:left">/blog/{{ $a->slug }}</div></td><td>{{ $a->keyword ?: '—' }}</td>
<td><span class="badge {{ $a->status === 'published' ? 'green' : 'gray' }}">{{ $a->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</span></td><td>{{ fa($a->views) }}</td>
<td><div class="row wrap" style="gap:6px"><a class="btn btn-sm btn-light" href="{{ route('admin.articles.edit', $a) }}"><x-icon name="edit" class="sm"/> ویرایش</a><a class="btn btn-sm btn-ghost" target="_blank" href="{{ $a->url() }}">نمایش</a>
<form method="post" action="{{ route('admin.articles.destroy', $a) }}" onsubmit="return confirm('مقاله حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" aria-label="حذف"><x-icon name="trash" class="sm"/></button></form></div></td></tr>
@empty<tr><td colspan="5" class="muted" style="text-align:center;padding:24px">مقاله‌ای نیست.</td></tr>@endforelse
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
