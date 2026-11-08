@if (session('ok'))<div class="alert ok"><x-icon name="check"/>{{ session('ok') }}</div>@endif
@if (session('warn'))<div class="alert warn"><x-icon name="info"/>{{ session('warn') }}</div>@endif
@if ($errors->any())
<div class="alert bad errbox" role="alert" id="errbox" data-keys="{{ json_encode(array_keys($errors->messages()), JSON_UNESCAPED_UNICODE) }}">
  <x-icon name="info"/>
  <div class="errbox-b">
    <b class="errbox-t">{{ $errors->count() > 1 ? 'چند مورد را باید اصلاح کنید' : 'یک مورد را باید اصلاح کنید' }}</b>
    <small class="errbox-h">روی هر مورد بزنید تا مستقیم به همان بخش بروید.</small>
    @php $seenErr = []; @endphp
    <ul>@foreach ($errors->messages() as $key => $msgs)@foreach ($msgs as $m)@continue(in_array($m, $seenErr, true))@php $seenErr[] = $m; @endphp<li><a href="#" data-err-key="{{ $key }}">{{ fa($m) }}</a></li>@endforeach @endforeach</ul>
  </div>
</div>
@endif
