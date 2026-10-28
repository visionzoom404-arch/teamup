@extends('layouts.panel')
@section('title', 'کاربران')
@section('content')
<div class="page-h"><h1>کاربران</h1></div>
<form class="row wrap mb" method="get"><input class="input" style="max-width:260px" name="q" value="{{ request('q') }}" placeholder="نام یا موبایل…"><select name="role" style="width:auto" onchange="this.form.submit()"><option value="">همه نقش‌ها</option><option value="guest" @selected(request('role')=='guest')>مهمان</option><option value="host" @selected(request('role')=='host')>میزبان</option><option value="admin" @selected(request('role')=='admin')>مدیر</option></select><button class="btn btn-sm btn-light">جستجو</button></form>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>کاربر</th><th>موبایل</th><th>نقش</th><th>کیف پول</th><th>عضویت</th><th></th></tr></thead><tbody>
@foreach($items as $u)<tr><td><div class="row"><img class="avatar sm" src="{{ $u->avatarUrl() }}" alt=""><b>{{ $u->name }}</b></div></td><td class="ltr">{{ $u->phone }}</td><td><span class="badge {{ ['admin'=>'dark','host'=>'orange','guest'=>'blue'][$u->role] }}">{{ ['admin'=>'مدیر','host'=>'میزبان','guest'=>'مهمان'][$u->role] }}</span> @if($u->host_verified)<span class="badge green">تأییدشده</span>@endif @unless($u->is_active)<span class="badge red">مسدود</span>@endunless</td><td>{{ toman($u->wallet, false) }}</td><td class="small">{{ jdate($u->created_at, 'Y/m/d') }}</td>
<td><div class="row" style="gap:6px">@if($u->isHost())<form method="post" action="{{ route('admin.users.verify', $u) }}">@csrf<button class="btn btn-sm btn-light">{{ $u->host_verified ? 'لغو تأیید' : 'تأیید هویت' }}</button></form>@endif @unless($u->isAdmin())<form method="post" action="{{ route('admin.users.toggle', $u) }}">@csrf<button class="btn btn-sm {{ $u->is_active ? 'btn-ghost' : 'btn-green' }}">{{ $u->is_active ? 'مسدود' : 'فعال‌سازی' }}</button></form>@endunless</div></td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
