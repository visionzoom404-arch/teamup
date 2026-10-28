@extends('layouts.panel')
@section('title', 'امکانات')
@section('content')
<div class="page-h"><h1>امکانات اقامتگاه</h1></div>
<form class="card mb" method="post" action="{{ route('admin.amenities.store') }}">@csrf<div class="grid4"><div class="field"><label>نام</label><input class="input" name="name" required></div><div class="field"><label>آیکن (ایموجی)</label><input class="input" name="icon" maxlength="4" placeholder="🔥"></div><div class="field"><label>گروه</label><select name="grp"><option value="feature">امکانات ویژه</option><option value="basic">امکانات پایه</option><option value="service">خدمات</option><option value="rules">قوانین و پذیرش</option></select></div><div class="field"><label>&nbsp;</label><button class="btn btn-block">افزودن</button></div></div></form>
<div class="chips">@foreach($items as $a)<form method="post" action="{{ route('admin.amenities.destroy', $a) }}" class="check" onsubmit="return confirm('حذف شود؟')">@csrf @method('DELETE')<span>{{ $a->icon }} {{ $a->name }}</span><button class="btn btn-sm btn-icon btn-ghost" style="width:26px;min-height:26px"><x-icon name="x" class="sm"/></button></form>@endforeach</div>
@endsection
