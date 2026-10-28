@extends('layouts.panel')
@section('title', 'اقامتگاه‌های من')
@section('content')
<div class="page-h"><h1>اقامتگاه‌های من</h1><a class="btn" href="{{ route('host.properties.create') }}"><x-icon name="plus"/> افزودن اقامتگاه</a></div>
@php $st = ['draft' => ['پیش‌نویس', 'gray'], 'pending' => ['در انتظار بررسی', 'gold'], 'approved' => ['منتشر شده', 'green'], 'rejected' => ['نیاز به اصلاح', 'red'], 'suspended' => ['تعلیق', 'red']]; @endphp
@forelse($items as $p)
<div class="item-row" style="align-items:flex-start;flex-wrap:wrap">
  <img class="thumb" src="{{ $p->cover() }}" alt="">
  <div class="grow">
    <div class="row between wrap"><b>{{ $p->title }}</b><span class="badge {{ $st[$p->status][1] }}">{{ $st[$p->status][0] }}</span></div>
    <div class="small muted">{{ $p->province }}، {{ $p->city }} · {{ $p->typeLabel() }} · {{ toman($p->price_weekday, false) }} تومان</div>
    @if($p->reject_reason)<div class="alert bad" style="margin:8px 0 0;padding:8px 12px">دلیل: {{ $p->reject_reason }}</div>@endif
    <div class="row wrap" style="margin-top:10px;gap:8px">
      <a class="btn btn-sm btn-light" href="{{ route('host.properties.edit', $p) }}"><x-icon name="edit" class="sm"/> ویرایش</a>
      <a class="btn btn-sm btn-blue" href="{{ route('host.calendar', $p) }}"><x-icon name="calendar" class="sm"/> تقویم و قیمت</a>
      <a class="btn btn-sm btn-light" href="{{ route('property', $p) }}"><x-icon name="eye" class="sm"/> مشاهده</a>
      @if(in_array($p->status, ['draft', 'rejected']))<form method="post" action="{{ route('host.properties.submit', $p) }}">@csrf<button class="btn btn-sm btn-green"><x-icon name="send" class="sm"/> ارسال برای بررسی</button></form>@endif
    </div>
  </div>
</div>
@empty
<div class="card empty"><x-icon name="home"/><h3>هنوز اقامتگاهی ثبت نکرده‌اید</h3><a class="btn" href="{{ route('host.properties.create') }}">ثبت اولین اقامتگاه</a></div>
@endforelse
@endsection
