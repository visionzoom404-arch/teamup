@extends('layouts.panel')
@section('title', 'صندوق اقساط')
@section('content')
<div class="page-h"><h1>صندوق پنهان رزرو اقساطی</h1></div>
<div class="alert warn"><x-icon name="lock"/><div>این صفحه فقط برای مدیر است. مهمان و میزبان از وجود این صندوق خبر ندارند؛ به مهمان فقط گفته می‌شود «به‌دلیل امتیاز بالا، رزرو اقساطی فعال شد».</div></div>
<div class="tiles"><div class="tile card"><div class="ic3"><x-i3 name="p_coin"/></div><b>{{ toman($total, false) }}</b><span>جمع صندوق (تومان)</span></div><div class="tile card"><div class="ic3"><x-i3 name="p_crown"/></div><b>{{ fa($unlocked) }}</b><span>مهمانِ دارای اقساط</span></div><div class="tile card"><div class="ic3"><x-i3 name="p_gift"/></div><b>{{ toman(setting('installment_threshold'), false) }}</b><span>سقف باز شدن اقساط</span></div></div>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>مهمان</th><th>اعتبار منقضی‌شده</th><th>پیشرفت تا سقف</th><th>اقساط</th><th></th></tr></thead><tbody>
@foreach($items as $u)@php $pc = min(100, (int) round($u->pool_amount / max(1, (int) setting('installment_threshold')) * 100)); @endphp<tr><td><b>{{ $u->name }}</b> <span class="ltr xs muted">{{ fa($u->phone) }}</span></td><td>{{ toman($u->pool_amount) }}</td><td style="min-width:140px"><div class="rbd-bar" style="height:8px"><i style="width:{{ $pc }}%"></i></div><span class="xs muted">{{ fa($pc) }}٪</span></td><td>@if($u->installment_unlocked)<span class="badge green">فعال</span>@else<span class="badge gray">قفل</span>@endif</td><td><a class="btn btn-sm btn-light" href="{{ route('admin.users.edit', $u) }}">ویرایش</a></td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
