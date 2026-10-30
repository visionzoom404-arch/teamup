@extends('layouts.panel')
@section('title', 'کاربران')
@section('content')
<div class="page-h"><h1>کاربران</h1><div class="row wrap"><a class="btn btn-sm" href="{{ route('admin.users.create') }}"><x-icon name="plus" class="sm"/> کاربر جدید</a><a class="btn btn-sm btn-light" href="{{ route('admin.export', 'users') }}"><x-icon name="download" class="sm"/> خروجی CSV</a></div></div>
<form class="row wrap mb" method="get"><input class="input" style="max-width:260px" name="q" value="{{ request('q') }}" placeholder="نام، موبایل یا کد ملی…">
  <select name="role" style="width:auto" onchange="this.form.submit()"><option value="">همه نقش‌ها</option>@foreach(['guest' => 'مهمان', 'host' => 'میزبان', 'admin' => 'مدیر'] as $k => $l)<option value="{{ $k }}" @selected(request('role') == $k)>{{ $l }}</option>@endforeach</select>
  <select name="id_status" style="width:auto" onchange="this.form.submit()"><option value="">همه‌ی وضعیت‌های هویت</option>@foreach(['pending' => 'در انتظار بررسی هویت', 'verified' => 'تأییدشده', 'rejected' => 'ردشده'] as $k => $l)<option value="{{ $k }}" @selected(request('id_status') == $k)>{{ $l }}</option>@endforeach</select><button class="btn btn-sm">جستجو</button></form>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>کاربر</th><th>موبایل</th><th>نقش</th><th>کیف پول</th><th>عضویت</th><th></th></tr></thead><tbody>
@foreach($items as $u)<tr>
  <td><div class="row"><img class="avatar sm" src="{{ $u->avatarUrl() }}" alt=""><div><b>{{ $u->name }}</b>@if($u->national_code)<div class="xs muted ltr">{{ fa($u->national_code) }}</div>@endif</div></div></td>
  <td class="ltr">{{ fa($u->phone) }}</td>
  <td><span class="badge {{ ['admin'=>'dark','host'=>'orange','guest'=>'blue'][$u->role] }}">{{ ['admin'=>'مدیر','host'=>'میزبان','guest'=>'مهمان'][$u->role] }}</span> @if($u->host_verified)<span class="badge green">تأییدشده</span>@endif @if($u->id_status === 'pending')<span class="badge gold">هویت در انتظار</span>@endif @unless($u->is_active)<span class="badge red">مسدود</span>@endunless</td>
  <td>{{ toman($u->wallet, false) }}</td><td class="small nowrap">{{ jdate($u->created_at, 'Y/m/d') }}</td>
  <td><div class="row" style="gap:6px;flex-wrap:wrap"><a class="btn btn-sm btn-light" href="{{ route('admin.users.edit', $u) }}"><x-icon name="edit" class="sm"/> ویرایش</a>
    @unless($u->isAdmin())<form method="post" action="{{ route('admin.users.impersonate', $u) }}">@csrf<button class="btn btn-sm btn-gold" title="ورود به پنل این کاربر"><x-icon name="eye" class="sm"/> ورود به پنل</button></form>@endunless</div></td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
