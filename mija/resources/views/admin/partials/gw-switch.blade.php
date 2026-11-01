@php $st = \App\Support\Gateways::status($k); $flag = \App\Support\Gateways::METHODS[$k][2]; @endphp
<div class="gw-sw">
  @if($st['live'])<span class="badge green">روشن و فعال</span>@elseif($st['flag'] && ! $st['ready'])<span class="badge red">روشن ولی ناقص — {{ $st['why'] }}</span>@else<span class="badge gray">خاموش</span>@endif
  <input type="hidden" name="keys[]" value="{{ $flag }}"><input type="hidden" name="{{ $flag }}" value="0">
  <label class="tgl"><input type="checkbox" name="{{ $flag }}" value="1" @checked($st['flag'])><span class="tr"></span><b class="sr-only">فعال</b></label>
</div>
