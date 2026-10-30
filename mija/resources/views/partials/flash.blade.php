@if (session('ok'))<div class="alert ok"><x-icon name="check"/>{{ session('ok') }}</div>@endif
@if (session('warn'))<div class="alert warn"><x-icon name="info"/>{{ session('warn') }}</div>@endif
@if ($errors->any())
<div class="alert bad"><x-icon name="info"/><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
@endif
