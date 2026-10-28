@extends('layouts.panel')
@section('title', 'اقامتگاه‌ها')
@section('content')
@php $st = ['draft' => ['پیش‌نویس', 'gray'], 'pending' => ['در انتظار بررسی', 'gold'], 'approved' => ['منتشر', 'green'], 'rejected' => ['ردشده', 'red'], 'suspended' => ['تعلیق', 'red']]; @endphp
<div class="page-h"><h1>اقامتگاه‌ها</h1></div>
<form class="row wrap mb" method="get"><input class="input" style="max-width:260px" name="q" value="{{ request('q') }}" placeholder="جستجو…"><select name="status" style="width:auto" onchange="this.form.submit()"><option value="">همه</option>@foreach($st as $k => $s)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $s[0] }}</option>@endforeach</select><button class="btn btn-sm btn-light">جستجو</button></form>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th></th><th>اقامتگاه</th><th>میزبان</th><th>قیمت</th><th>وضعیت</th><th></th></tr></thead><tbody>
@foreach($items as $p)<tr><td><img src="{{ $p->cover() }}" width="64" height="48" style="border-radius:10px;object-fit:cover" alt=""></td><td><b>{{ $p->title }}</b><div class="xs muted">{{ $p->province }}، {{ $p->city }}</div></td><td>{{ $p->host->name }}</td><td>{{ toman($p->price_weekday, false) }}</td><td><span class="badge {{ $st[$p->status][1] }}">{{ $st[$p->status][0] }}</span> @if($p->featured)<span class="badge gold">ویژه</span>@endif</td><td><a class="btn btn-sm btn-light" href="{{ route('admin.properties.show', $p) }}">بررسی</a></td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
