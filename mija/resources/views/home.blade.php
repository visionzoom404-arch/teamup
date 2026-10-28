@extends('layouts.app')
@section('title', 'رزرو آنلاین ویلا و کلبه')
@section('bare')
<section class="hero">
  <div class="container">
    <span class="badge gold" style="margin-bottom:14px"><x-icon name="sun" class="sm"/> اقامتی به‌یادماندنی، مستقیم از میزبان</span>
    <h1>ویلا و کلبه‌ی رویایی‌ات را <span class="grad-text">همین امروز رزرو کن</span></h1>
    <p class="sub">هزاران اقامتگاه تأییدشده در شمال، کویر، جنگل و کوهستان؛ با قیمت شفاف، پرداخت امن و ضمانت بازگشت وجه.</p>

    <form class="searchbar glass" action="{{ route('search') }}" method="get">
      <div><label>مقصد</label><select name="province"><option value="">همه‌ی استان‌ها</option>@foreach(config('mija.provinces') as $pv => $cs)<option>{{ $pv }}</option>@endforeach</select></div>
      <div class="rp" data-rangepicker data-unavailable="[]">
        <label>تاریخ ورود و خروج</label>
        <input type="hidden" name="check_in" data-in><input type="hidden" name="check_out" data-out>
        <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب تاریخ</b></div><div><small>خروج</small><b data-label-out>انتخاب تاریخ</b></div></div>
        <div class="rp-pop glass"></div>
      </div>
      <div><label>تعداد مسافر</label><select name="guests"><option value="">هر تعداد</option>@foreach([2,4,6,8,10,15] as $n)<option value="{{ $n }}">{{ fa($n) }} نفر و بیشتر</option>@endforeach</select></div>
      <button class="btn btn-lg"><x-icon name="search"/> جستجو</button>
    </form>
  </div>
</section>

<div class="container">
  <div class="cats">
    @foreach([['villa','🏡','ویلا'],['cottage','🛖','کلبه'],['garden_villa','🌳','ویلا باغ'],['tiny','🪵','خانه چوبی'],['eco','⛺','بوم‌گردی'],['suite','🛏️','سوئیت'],['farm','🌾','مزرعه و باغ'],['apartment','🏢','آپارتمان']] as [$k,$e,$n])
      <a class="cat" href="{{ route('search', ['type' => [$k]]) }}"><span class="em">{{ $e }}</span>{{ $n }}</a>
    @endforeach
  </div>

  @if($featured->count())
  <section class="sec">
    <div class="sec-h"><div><h2>اقامتگاه‌های <span class="grad-text">ویژه</span></h2><p>انتخاب‌شده توسط تیم می‌جا</p></div><a class="btn btn-sm btn-light" href="{{ route('search') }}">مشاهده همه <x-icon name="chev-l" class="sm"/></a></div>
    <div class="grid-cards">@foreach($featured as $p)@include('partials.pcard')@endforeach</div>
  </section>
  @endif

  @if($destinations->count())
  <section class="sec">
    <div class="sec-h"><div><h2>مقصدهای محبوب</h2><p>پرطرفدارترین شهرها برای سفر</p></div></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
      @foreach($destinations as $i => $d)
        @php $g = [['#FF3B3B','#FF9A1F'],['#2a2d38','#E5202E'],['#4fb2e6','#2FAE6B'],['#FFB21A','#FF5A1F'],['#B80F22','#111216'],['#7CC8F0','#FF7A00'],['#2FAE6B','#F5B301'],['#FF7A00','#B80F22']][$i % 8]; @endphp
        <a class="dest" href="{{ route('search', ['city' => $d->city]) }}">
          <div class="bgart" style="background:linear-gradient(135deg,{{ $g[0] }},{{ $g[1] }})"><svg viewBox="0 0 200 200" width="100%" height="100%" preserveAspectRatio="xMidYMax slice" opacity=".4"><path d="M0 200V120l40-50 36 40 44-70 50 80 30-30v110z" fill="#fff"/></svg></div>
          <div><b>{{ $d->city }}</b><small>{{ $d->province }} · {{ fa($d->c) }} اقامتگاه</small></div>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  <section class="sec">
    <div class="sec-h"><div><h2>تازه‌ترین اقامتگاه‌ها</h2><p>تازه به می‌جا پیوسته‌اند</p></div></div>
    <div class="grid-cards">@foreach($latest as $p)@include('partials.pcard')@endforeach</div>
    @if(! $latest->count())<div class="card empty"><x-icon name="home"/><p>هنوز اقامتگاهی ثبت نشده است.</p></div>@endif
  </section>

  <section class="sec">
    <div class="sec-h"><div><h2>چرا <span class="grad-text">می‌جا</span>؟</h2></div></div>
    <div class="feat">
      <div class="card"><div class="ico"><x-icon name="shield" class="lg"/></div><b>پرداخت امن و ضمانت</b><p class="small muted">وجه تا روز ورود نزد می‌جا می‌ماند و در صورت لغو طبق قوانین بازگردانده می‌شود.</p></div>
      <div class="card"><div class="ico blue"><x-icon name="bolt" class="lg"/></div><b>رزرو فوری</b><p class="small muted">اقامتگاه‌های رزرو فوری بدون انتظار برای تأیید میزبان، همان لحظه قطعی می‌شوند.</p></div>
      <div class="card"><div class="ico green"><x-icon name="tag" class="lg"/></div><b>قیمت شفاف</b><p class="small muted">قیمت نهایی شامل همه‌ی هزینه‌ها پیش از پرداخت نمایش داده می‌شود؛ بدون هزینه‌ی پنهان.</p></div>
      <div class="card"><div class="ico dark"><x-icon name="star" class="lg"/></div><b>نظرات واقعی</b><p class="small muted">فقط مهمانانی که اقامت داشته‌اند می‌توانند نظر ثبت کنند.</p></div>
    </div>
  </section>

  <section class="sec">
    <div class="cta">
      <div class="row wrap between" style="gap:20px">
        <div style="max-width:560px"><h2 style="color:#fff">ملک داری؟ <span style="color:var(--gold-l)">میزبان می‌جا شو</span></h2><p style="opacity:.85;margin:0">اقامتگاهت را رایگان ثبت کن، تقویم و قیمت‌ها را خودت مدیریت کن و درآمدت را مستقیم به حساب بانکی‌ات بگیر.</p></div>
        <a class="btn btn-gold btn-lg" href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه <x-icon name="chev-l"/></a>
      </div>
    </div>
  </section>
</div>
@endsection
