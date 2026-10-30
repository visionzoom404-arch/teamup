@extends('layouts.panel')
@section('title', 'ارسال همگانی')
@section('content')
<div class="page-h"><h1>ارسال همگانی</h1></div>
@include('partials.help', ['title' => 'کاربرد', 'text' => 'برای اطلاع‌رسانی (کمپین، تغییر قوانین، تخفیف ویژه) می‌توانید به همه‌ی مهمانان یا میزبانان اعلان داخل سایت و در صورت تیک «پیامک» پیامک (همراه لینک) بفرستید.'])
<form method="post" action="{{ route('admin.broadcast.send') }}" class="card" onsubmit="return confirm('ارسال شود؟')">@csrf
  <div class="grid2"><div class="field"><label>مخاطبان</label><select name="to"><option value="guest">مهمانان ({{ fa($counts['guest']) }})</option><option value="host">میزبانان ({{ fa($counts['host']) }})</option><option value="all">همه</option></select></div>
  <div class="field"><label>لینک (اختیاری)</label><input class="input" dir="ltr" name="url" placeholder="{{ url('/') }}"></div></div>
  <div class="field"><label>عنوان</label><input class="input" name="title" maxlength="80" required></div>
  <div class="field"><label>متن</label><textarea class="input" name="body" maxlength="300"></textarea></div>
  <label class="check"><input type="checkbox" name="sms" value="1"> همزمان پیامک هم ارسال شود (هزینه‌ی پیامک از پنل شما کسر می‌شود)</label>
  <button class="btn btn-lg mt">ارسال</button></form>
@endsection
