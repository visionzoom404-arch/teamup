@extends('layouts.panel')
@section('title', 'گزارش خطاها')
@section('content')
<div class="page-h"><div><h1>گزارش خطاها</h1><div class="muted">خطاهای سرور به زبان ساده: چه شده، چرا شده و چطور رفع کنیم</div></div>
  @if(count($groups))<form method="post" action="{{ route('admin.errors.clear') }}" onsubmit="return confirm('همه‌ی گزارش‌ها پاک شود؟ (بعد از رفع مشکل برای شروع تازه)')">@csrf<button class="btn btn-sm btn-light"><x-icon name="trash" class="sm"/> پاک‌کردن گزارش‌ها</button></form>@endif</div>
@error('fix')<div class="alert bad">{{ fa($message) }}</div>@enderror
@forelse($groups as $g)
@php $e = $g['ex']; [$lv, $lc] = $levels[$e['level']]; @endphp
<div class="card mb er-card {{ $e['level'] }}">
  <div class="row between wrap" style="gap:8px"><div class="row wrap" style="gap:8px"><span class="badge {{ $lc }}">{{ $lv }}</span><b class="er-t">{{ $e['title'] }}</b></div>
    <span class="xs muted">@if($g['count'] > 1){{ fa($g['count']) }} بار · @endif آخرین: {{ jdate(\Illuminate\Support\Carbon::parse($g['last']), 'Y/m/d H:i') }}</span></div>
  <div class="er-body">
    <p><b>چه شده؟</b> {{ $e['what'] }}</p>
    <p><b>چرا؟</b> {{ $e['why'] }}</p>
    <p><b>چطور رفع کنیم؟</b> {{ $e['fix'] }}</p>
    @if($e['cmd'])<pre class="er-cmd" id="cmd{{ $loop->index }}" dir="ltr">{{ $e['cmd'] }}</pre><button type="button" class="btn btn-sm btn-light" data-copy="#cmd{{ $loop->index }}">کپی دستور</button>@endif
    <div class="row wrap" style="gap:8px;margin-top:8px">
      @if($e['route'])<a class="btn btn-sm" href="{{ route($e['route'][0], $e['route'][1]) }}"><x-icon name="settings" class="sm"/> برو و رفعش کن: {{ $e['route'][2] }}</a>@endif
      @if($e['action'])<form method="post" action="{{ route('admin.errors.fix') }}">@csrf<input type="hidden" name="action" value="{{ $e['action'] }}"><button class="btn btn-sm btn-green"><x-icon name="bolt" class="sm"/> رفع خودکار: {{ $fixes[$e['action']][0] }}</button></form>@endif
    </div>
  </div>
  <details class="er-tech"><summary>جزئیات فنی (برای پشتیبان فنی)</summary>
    <div id="tech{{ $loop->index }}" dir="ltr" style="text-align:left;overflow-wrap:anywhere">{{ $g['msg'] }}@if($g['file']) — {{ $g['file'] }}@endif</div>
    <button type="button" class="btn btn-sm btn-light" data-copy="#tech{{ $loop->index }}">کپی گزارش</button>
  </details>
</div>
@empty
<div class="card empty"><x-icon name="check"/><p>خطایی ثبت نشده است. همه‌چیز سالم است.</p></div>
@endforelse
@endsection
