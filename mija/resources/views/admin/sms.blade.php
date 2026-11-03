@extends('layouts.panel')
@section('title', 'گزارش پیامک')
@section('content')
<div class="page-h"><h1>گزارش پیامک‌ها</h1><a class="btn btn-sm btn-light" href="{{ route('admin.settings', ['tab' => 'sms']) }}">تنظیمات پیامک</a></div>
<div class="tiles">@foreach(['sent' => ['ارسال‌شده', 'p_phone'], 'failed' => ['ناموفق', 'p_bell'], 'logged' => ['فقط ثبت‌شده (ارسال خاموش)', 'p_chat']] as $k => [$l, $ic])<div class="tile card"><div class="ic3"><x-i3 name="{{ $ic }}"/></div><b>{{ fa($stats[$k] ?? 0) }}</b><span>{{ $l }}</span></div>@endforeach</div>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>زمان</th><th>به</th><th>متن</th><th>وضعیت</th><th>روش</th></tr></thead><tbody>
@foreach($items as $s)<tr><td class="small nowrap">{{ jdate($s->created_at, 'Y/m/d H:i') }}</td><td class="ltr">{{ fa($s->phone) }}</td><td class="small" style="white-space:pre-line;max-width:420px">{{ $s->message }}</td><td><span class="badge {{ ['sent' => 'green', 'failed' => 'red', 'logged' => 'gray', 'retried' => 'gray'][$s->status] ?? 'gray' }}">{{ ['sent' => 'ارسال شد', 'failed' => 'ناموفق', 'logged' => 'ثبت شد', 'retried' => 'ارسال مجدد شد'][$s->status] ?? $s->status }}</span>@if($s->retry_at)<div class="xs muted">تلاش مجدد: {{ jdate($s->retry_at, 'H:i') }}</div>@endif @if($s->response && $s->status === 'failed')<div class="xs" style="color:#B42318;max-width:260px">{{ \Illuminate\Support\Str::limit($s->response, 140) }}</div>@endif</td><td class="small">{{ ['pattern' => 'پترن', 'plain' => 'عادی'][$s->via] ?? '—' }}@if($s->attempts)<div class="xs muted">تلاش {{ fa($s->attempts + 1) }}</div>@endif</td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
