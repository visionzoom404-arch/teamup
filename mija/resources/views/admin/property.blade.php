@extends('layouts.panel')
@section('title', 'بررسی '.$p->title)
@section('content')
<div class="page-h"><div><a href="{{ route('admin.properties') }}" class="small muted">← اقامتگاه‌ها</a><h1>{{ $p->title }}</h1></div><a class="btn btn-sm btn-light" target="_blank" href="{{ route('property', $p) }}"><x-icon name="eye" class="sm"/> نمای عمومی</a></div>
<div class="imgs mb">@foreach($p->images as $im)<div class="im"><img src="{{ $im->url() }}" alt=""></div>@endforeach</div>
<div class="grid2" style="gap:18px;align-items:start">
  <div class="card"><h3>مشخصات</h3>
    <div class="qrow"><span>میزبان</span><b>{{ $p->host->name }} <span class="ltr">{{ $p->host->phone }}</span></b></div>
    <div class="qrow"><span>نوع / محل</span><b>{{ $p->typeLabel() }} · {{ $p->province }}، {{ $p->city }}</b></div>
    <div class="qrow"><span>ظرفیت</span><b>{{ fa($p->capacity_base) }} تا {{ fa($p->capacity_max) }} نفر · {{ fa($p->bedrooms) }} خواب</b></div>
    <div class="qrow"><span>قیمت میان‌هفته / آخر هفته</span><b>{{ toman($p->price_weekday, false) }} / {{ toman($p->price_weekend, false) }}</b></div>
    <div class="qrow"><span>سیاست لغو</span><b>{{ $p->policy()['name'] }}</b></div>
    <div class="qrow"><span>رزرو فوری</span><b>{{ $p->instant_booking ? 'بله' : 'خیر' }}</b></div>
    <hr class="divider" style="margin:12px 0"><p class="small" style="white-space:pre-line">{{ $p->description }}</p>
    <div class="chips">@foreach($p->amenities as $a)<span class="badge">{{ $a->icon }} {{ $a->name }}</span>@endforeach</div>
  </div>
  <div class="stack">
    <div class="card"><h3>تصمیم بررسی</h3><p class="small muted">وضعیت فعلی: <b>{{ ['draft'=>'پیش‌نویس','pending'=>'در انتظار بررسی','approved'=>'منتشر','rejected'=>'ردشده','suspended'=>'تعلیق'][$p->status] }}</b></p>
      <form method="post" action="{{ route('admin.properties.status', $p) }}" class="stack">@csrf<input class="input" name="reason" placeholder="توضیح برای میزبان (برای رد/تعلیق)" value="{{ $p->reject_reason }}">
        <div class="row wrap"><button class="btn btn-green" name="status" value="approved"><x-icon name="check"/> تأیید و انتشار</button><button class="btn btn-gold" name="status" value="rejected">نیاز به اصلاح</button><button class="btn btn-dark" name="status" value="suspended">تعلیق</button></div></form></div>
    <div class="card"><h3>ویترین صفحه اول</h3><form method="post" action="{{ route('admin.properties.feature', $p) }}">@csrf<button class="btn {{ $p->featured ? 'btn-ghost' : 'btn-gold' }}"><x-icon name="star" class="sm"/> {{ $p->featured ? 'حذف از ویترین' : 'نمایش در ویترین' }}</button></form></div>
  </div>
</div>
@endsection
