@extends('layouts.panel')
@section('title', 'مدارک میزبانان')
@section('content')
<div class="page-h"><div><h1>مدارک میزبانان</h1><div class="muted">کارت ملی، عکس همراه کارت، مدرک مالکیت و اطلاعات بانکی که میزبان‌ها هنگام پذیرش می‌فرستند</div></div></div>
@include('partials.help', ['title' => 'چطور بررسی کنم؟', 'text' => 'مدارک هر میزبان را کنار هم می‌بینید. نام و کد ملی را با کارت ملی مقایسه کنید و نام صاحب حساب بانکی باید همان نام میزبان باشد. با «تأیید» میزبان نشان تأیید هویت می‌گیرد و پیامک می‌شود؛ با «رد» باید دلیل بنویسید تا میزبان اصلاح و دوباره ارسال کند.'])
@include('partials.flash')
<div class="tabs">@foreach(['pending' => 'در انتظار بررسی ('.fa($counts['pending'] ?? 0).')', 'verified' => 'تأییدشده', 'rejected' => 'ردشده', 'none' => 'بدون مدرک', 'all' => 'همه'] as $k => $l)<a href="{{ route('admin.documents', ['tab' => $k]) }}" class="{{ $tab === $k ? 'on' : '' }}">{{ $l }}</a>@endforeach</div>
@forelse($items as $h)
@php $st = ['pending' => ['در انتظار بررسی', 'gold'], 'verified' => ['تأییدشده', 'green'], 'rejected' => ['ردشده', 'red']][$h->id_status] ?? ['بدون مدرک', 'gray']; @endphp
<article class="card mb dc">
  <header class="dc-h"><div class="dc-who"><span class="avatar" style="width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,#F8BE55,#E07F08);color:#fff;font-weight:800">{{ mb_substr($h->name, 0, 1) }}</span><div><b>{{ $h->name }}</b><div class="xs muted"><span class="ltr">{{ fa($h->phone) }}</span> · ثبت‌نام {{ jdate($h->created_at, 'j F Y') }}@if($h->docs_submitted_at) · مدارک: {{ jdate($h->docs_submitted_at, 'j F H:i') }}@endif</div></div></div><span class="badge {{ $st[1] }}">{{ $st[0] }}</span></header>
  <div class="dc-grid">
    <div class="dc-info">
      <div class="qrow"><span>کد ملی</span><b class="ltr">{{ $h->national_code ? fa($h->national_code) : '—' }}</b></div>
      <div class="qrow"><span>تاریخ تولد</span><b>{{ $h->birth_date ? fa($h->birth_date) : '—' }}</b></div>
      <div class="qrow"><span>شماره کارت</span><b class="ltr">{{ $h->card_number ? fa($h->card_number) : '—' }}@if($h->bank_name) <small class="muted">({{ $h->bank_name }})</small>@endif</b></div>
      <div class="qrow"><span>شبا</span><b class="ltr">{{ $h->sheba ?: '—' }}</b></div>
      <div class="qrow"><span>صاحب حساب</span><b>{{ $h->bank_owner ?: '—' }}@if($h->bank_owner && $h->name && $h->bank_owner !== $h->name) <span class="badge red" style="padding:0 8px">با نام میزبان فرق دارد</span>@endif</b></div>
      @if($h->id_note)<div class="alert warn" style="margin:8px 0 0"><x-icon name="info"/>دلیل ردِ قبلی: {{ $h->id_note }}</div>@endif
      <a class="btn btn-sm btn-light" style="margin-top:8px" href="{{ route('admin.users.edit', $h) }}">پروفایل و ویرایش</a>
    </div>
    <div class="dc-docs">
      @foreach(['id' => ['کارت ملی', 'id_photo'], 'selfie' => ['عکس همراه کارت', 'selfie_photo'], 'ownership' => ['مدرک مالکیت / اجاره', 'ownership_doc']] as $t => [$lbl, $col])
        <div class="dc-doc"><small>{{ $lbl }}</small>
          @if($h->$col)@if(str_ends_with(strtolower($h->$col), '.pdf'))<a class="dc-pdf" target="_blank" href="{{ route('admin.documents.file', [$h, $t]) }}"><x-icon name="file"/> مشاهده‌ی PDF</a>@else<a target="_blank" href="{{ route('admin.documents.file', [$h, $t]) }}"><img src="{{ route('admin.documents.file', [$h, $t]) }}" alt="{{ $lbl }}" loading="lazy"></a>@endif
          @else<div class="dc-none">ارسال نشده</div>@endif</div>
      @endforeach
    </div>
  </div>
  @if($h->id_status !== 'verified' || true)
  <form method="post" action="{{ route('admin.documents.decide', $h) }}" class="dc-act">@csrf
    <input class="input" name="note" placeholder="دلیل رد (برای میزبان؛ فقط هنگام رد لازم است)" maxlength="200">
    <button class="btn btn-green btn-sm" name="action" value="approve" @disabled(! $h->national_code)>تأیید مدارک</button>
    <button class="btn btn-sm btn-ghost" name="action" value="reject">رد مدارک</button>
  </form>@endif
</article>
@empty
<div class="card empty"><x-icon name="check"/><p>مدرکی در این بخش نیست.</p></div>
@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
