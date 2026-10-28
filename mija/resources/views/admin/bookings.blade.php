@extends('layouts.panel')
@section('title', 'رزروها')
@section('content')
<div class="page-h"><h1>همه‌ی رزروها</h1></div>
<form class="row wrap mb" method="get"><input class="input" style="max-width:220px" name="q" value="{{ request('q') }}" placeholder="کد رزرو…" dir="ltr"><select name="status" style="width:auto" onchange="this.form.submit()"><option value="">همه وضعیت‌ها</option>@foreach(config('mija.booking_statuses') as $k => $s)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $s[0] }}</option>@endforeach</select><button class="btn btn-sm btn-light">جستجو</button></form>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>کد</th><th>اقامتگاه</th><th>مهمان</th><th>میزبان</th><th>تاریخ</th><th>مبلغ</th><th>کمیسیون</th><th>وضعیت</th></tr></thead><tbody>
@foreach($items as $b)<tr><td><a class="ltr" style="color:var(--red-d);font-weight:700" href="{{ route('admin.bookings.show', $b) }}">{{ $b->code }}</a></td><td>{{ \Illuminate\Support\Str::limit($b->property->title, 28) }}</td><td>{{ $b->guest->name }}</td><td>{{ $b->host->name }}</td><td class="small nowrap">{{ jdate($b->check_in, 'Y/m/d') }} ({{ fa($b->nights) }} شب)</td><td>{{ toman($b->total, false) }}</td><td>{{ toman($b->commission, false) }}</td><td><span class="badge {{ $b->statusColor() }}">{{ $b->statusLabel() }}</span></td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
