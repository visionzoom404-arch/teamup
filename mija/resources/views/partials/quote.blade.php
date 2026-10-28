<div class="qrow"><span>{{ fa(number_format($q['avg_night'])) }} × {{ fa($q['nights']) }} شب</span><span>{{ toman($q['base']) }}</span></div>
@if($q['extra'] > 0)<div class="qrow"><span>نفر اضافه ({{ fa($q['extra_guests']) }} نفر)</span><span>{{ toman($q['extra']) }}</span></div>@endif
@if($q['cleaning'] > 0)<div class="qrow"><span>هزینه نظافت</span><span>{{ toman($q['cleaning']) }}</span></div>@endif
@if($q['long_discount'] > 0)<div class="qrow"><span>تخفیف اقامت طولانی ({{ fa($q['long_pct']) }}٪)</span><span class="off">−{{ toman($q['long_discount']) }}</span></div>@endif
@if($q['coupon_discount'] > 0)<div class="qrow"><span>کد تخفیف {{ $q['coupon'] }}</span><span class="off">−{{ toman($q['coupon_discount']) }}</span></div>@endif
@if($q['service_fee'] > 0)<div class="qrow"><span>کارمزد خدمات</span><span>{{ toman($q['service_fee']) }}</span></div>@endif
@if($q['coupon_error'])<div class="err">{{ $q['coupon_error'] }}</div>@endif
<div class="qrow total"><span>مبلغ قابل پرداخت</span><span>{{ toman($q['total']) }}</span></div>
