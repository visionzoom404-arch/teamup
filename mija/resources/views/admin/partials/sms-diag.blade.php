@php
  $diag = session('sms_diag');
  $st = \App\Support\Sms::status();
  $cr = $st['state'] === 'ok' ? ['v' => $st['credit'], 'at' => $st['at']] : null;
  $icon = ['ok' => 'check', 'fail' => 'x', 'warn' => 'info', 'skip' => 'clock'];
  $lbl = ['ok' => 'درست', 'fail' => 'اشتباه', 'warn' => 'بررسی کنید', 'skip' => 'انجام نشد'];
@endphp
<div class="card mb sd">
  <div class="sd-h">
    <div><h3 style="margin:0"><x-i3 name="p_shield" class="sm"/> بررسی اتصال پنل پیامک</h3><div class="muted small">با یک لمس، مرحله‌ی اشتباه را پیدا کنید</div></div>
    <div class="sd-credit {{ $cr ? 'on' : ($st['state'] === 'fail' ? 'off' : '') }}"><small>@if($cr)متصل ✓ — اعتبار پنل@elseif($st['state'] === 'fail')اتصال برقرار نیست@else توکن وارد نشده@endif</small><b>{{ $cr ? fa(number_format($cr['v'])) : '—' }}</b>@if($cr)<em>{{ jdate(\Illuminate\Support\Carbon::parse($cr['at']), 'H:i') }}</em>@elseif($st['state'] === 'fail')<em>{{ \Illuminate\Support\Str::limit($st['error'], 70) }}</em>@endif</div>
  </div>
  <form method="post" action="{{ route('admin.sms.check') }}" class="sd-form">@csrf
    <input class="input" dir="ltr" name="test" value="{{ old('test', auth()->user()->phone) }}" placeholder="شماره برای پیامک آزمایشی (اختیاری)" inputmode="numeric">
    <button class="btn" name="send" value="0">بررسی اتصال</button>
    <button class="btn btn-light" name="send" value="1">بررسی + ارسال پیامک آزمایشی</button>
  </form>
  @if($diag)
  <ol class="sd-steps">
    @foreach($diag as $i => $st)
    <li class="sd-step {{ $st[2] }}" style="animation-delay:{{ $i * 90 }}ms"><span class="sd-dot"><x-icon name="{{ $icon[$st[2]] }}"/></span><div><b>{{ $st[1] }}</b><span class="sd-tag">{{ $lbl[$st[2]] }}</span><p>{{ $st[3] }}</p></div></li>
    @endforeach
  </ol>
  @php $bad = collect($diag)->firstWhere(fn ($x) => $x[2] === 'fail'); @endphp
  @if($bad)<div class="alert bad"><x-icon name="info"/><div>اولین مشکل: <b>{{ $bad[1] }}</b> — {{ $bad[3] }}</div></div>@else<div class="alert ok"><x-icon name="check"/>همه‌ی مرحله‌ها درست است.</div>@endif
  @endif
</div>
