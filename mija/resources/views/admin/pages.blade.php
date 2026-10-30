@extends('layouts.panel')
@section('title', 'صفحه‌ها و متن‌ها')
@section('content')
<div class="page-h"><h1>صفحه‌ها و متن‌ها</h1></div>
@include('partials.help', ['title' => 'ویرایش متن‌ها', 'text' => 'هر صفحه (قوانین مهمان، قوانین میزبان، درباره ما …) را می‌توانید با HTML ساده ویرایش کنید. با «بازگشت به پیش‌فرض» متن اصلی برمی‌گردد. متن‌های کوتاه سایت (تلفن، فوتر) در «تنظیمات ← متن‌ها» هستند.', 'open' => true])
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>صفحه</th><th>آدرس</th><th>وضعیت</th><th></th></tr></thead><tbody>
@foreach($slugs as $sl => $t)<tr><td><b>{{ $t }}</b></td><td class="ltr small">/page/{{ $sl }}</td><td>@if(isset($saved[$sl]))<span class="badge green">ویرایش‌شده {{ jdate($saved[$sl], 'j F') }}</span>@else<span class="badge gray">پیش‌فرض</span>@endif</td><td><a class="btn btn-sm btn-light" href="{{ route('admin.pages.edit', $sl) }}"><x-icon name="edit" class="sm"/> ویرایش</a> <a class="btn btn-sm btn-ghost" target="_blank" href="{{ route('page', $sl) }}">مشاهده</a></td></tr>@endforeach
</tbody></table></div></div>
@endsection
