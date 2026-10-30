@extends('layouts.panel')
@section('title', 'اپلیکیشن اندروید')
@section('content')
@php $latest = $items->where('published', true)->sortByDesc('version_code')->first(); @endphp
<div class="page-h"><h1>اپلیکیشن اندروید</h1><a class="btn btn-light btn-sm" href="{{ route('app') }}" target="_blank">مشاهده‌ی صفحه‌ی دانلود</a></div>
@include('partials.help', ['title' => 'این بخش چه کار می‌کند؟', 'text' => 'هر نسخه‌ی تازه‌ی اپ را اینجا بارگذاری کنید: فایل «direct» برای دانلود از سایت و درون‌برنامه‌ای، و فایل «store» برای آپلود در پنل کافه‌بازار و مایکت. با ثبت نسخه‌ی جدید، همه‌ی کاربران اپ پیام به‌روزرسانی می‌گیرند (بازار/مایکت‌ی‌ها به فروشگاه خودشان هدایت می‌شوند و بقیه از سایت دانلود می‌کنند). «شماره‌ی نسخه» (version code) باید از نسخه‌ی قبلی بزرگ‌تر باشد.', 'open' => $items->isEmpty()])

<div class="grid-2 mb" style="display:grid;gap:14px">
  <div class="card">
    <h3>انتشار نسخه‌ی جدید</h3>
    <form method="post" action="{{ route('admin.app.store') }}" enctype="multipart/form-data">@csrf
      <div class="row2" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="field"><label>نام نسخه (مثلاً 1.1.0)</label><input class="input" dir="ltr" name="version_name" value="{{ old('version_name') }}" placeholder="1.1.0" required>@error('version_name')<div class="msg-e">{{ $message }}</div>@enderror</div>
        <div class="field"><label>شماره‌ی نسخه (عدد)</label><input class="input" dir="ltr" inputmode="numeric" name="version_code" value="{{ old('version_code', ($latest->version_code ?? 0) + 1) }}" required>@error('version_code')<div class="msg-e">{{ $message }}</div>@enderror</div>
      </div>
      <div class="field"><label>تغییرات نسخه (هر خط یک مورد)</label><textarea class="input" name="changelog" rows="4" placeholder="رفع مشکل ...&#10;افزودن ...">{{ old('changelog') }}</textarea></div>
      <div class="field"><label>فایل APK مستقیم (direct) — الزامی</label><input class="input" type="file" name="direct" accept=".apk" required>@error('direct')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>فایل APK فروشگاهی (store) — برای بازار و مایکت (اختیاری)</label><input class="input" type="file" name="store" accept=".apk">@error('store')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <label class="check"><input type="checkbox" name="force" value="1"> به‌روزرسانی اجباری (نسخه‌های قدیمی‌تر باید حتماً به‌روز شوند)</label>
      <button class="btn" style="margin-top:12px">انتشار نسخه</button>
    </form>
  </div>
  <div class="card">
    <h3>لینک فروشگاه‌ها</h3>
    <p class="small muted">بعد از انتشار اپ در بازار و مایکت، لینک صفحه‌ی اپ را اینجا بگذارید تا دکمه‌ها در صفحه‌ی دانلود فعال شوند. شناسه‌ی بسته: <b class="ltr">{{ \App\Models\AppRelease::PACKAGE }}</b></p>
    <form method="post" action="{{ route('admin.app.stores') }}">@csrf
      <div class="field"><label>لینک کافه‌بازار</label><input class="input" dir="ltr" name="app_bazaar_url" value="{{ old('app_bazaar_url', $bazaar) }}" placeholder="https://cafebazaar.ir/app/{{ \App\Models\AppRelease::PACKAGE }}">@error('app_bazaar_url')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>لینک مایکت</label><input class="input" dir="ltr" name="app_myket_url" value="{{ old('app_myket_url', $myket) }}" placeholder="https://myket.ir/app/{{ \App\Models\AppRelease::PACKAGE }}">@error('app_myket_url')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <button class="btn">ذخیره</button>
    </form>
  </div>
</div>

<div class="card">
  <h3>نسخه‌ها ({{ fa($items->count()) }})</h3>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>نسخه</th><th>شماره</th><th>تاریخ</th><th>حجم</th><th>دانلود</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    @forelse($items as $r)
      <tr>
        <td><b>{{ fa($r->version_name) }}</b>@if($latest && $latest->id === $r->id)<span class="badge green" style="margin-inline-start:6px">آخرین</span>@endif</td>
        <td class="ltr">{{ fa($r->version_code) }}</td>
        <td>{{ jdate($r->created_at, 'j F Y') }}</td>
        <td>{{ $r->direct_size ? fa(number_format($r->direct_size / 1048576, 1)).' MB' : '—' }}</td>
        <td>{{ fa($r->downloads) }}</td>
        <td>
          <form method="post" action="{{ route('admin.app.toggle', $r) }}" style="display:inline">@csrf<input type="hidden" name="field" value="published"><button class="badge {{ $r->published ? 'green' : 'gray' }}" style="border:0;cursor:pointer">{{ $r->published ? 'منتشر' : 'پنهان' }}</button></form>
          <form method="post" action="{{ route('admin.app.toggle', $r) }}" style="display:inline">@csrf<input type="hidden" name="field" value="force"><button class="badge {{ $r->force ? 'red' : 'gray' }}" style="border:0;cursor:pointer" title="اجباری بودن">{{ $r->force ? 'اجباری' : 'اختیاری' }}</button></form>
        </td>
        <td style="white-space:nowrap">
          @if($r->direct_file)<a class="btn btn-sm btn-light" href="{{ route('admin.app.file', [$r, 'direct']) }}">direct</a>@endif
          @if($r->store_file)<a class="btn btn-sm btn-light" href="{{ route('admin.app.file', [$r, 'store']) }}">store</a>@endif
          <form method="post" action="{{ route('admin.app.destroy', $r) }}" style="display:inline" onsubmit="return confirm('این نسخه حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-light" title="حذف"><x-icon name="trash" class="sm"/></button></form>
        </td>
      </tr>
      @if($r->direct_sha256)<tr class="sub"><td colspan="7"><span class="xs muted">SHA-256:</span> <code class="xs ltr" style="word-break:break-all">{{ $r->direct_sha256 }}</code></td></tr>@endif
    @empty
      <tr><td colspan="7" class="muted">هنوز نسخه‌ای ثبت نشده است.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
@endsection
