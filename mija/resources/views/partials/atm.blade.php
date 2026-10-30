{{-- کارت بانکی انیمیشنی کیف پول: $kind host|guest، $amount، $label، $holder، $sub (شبا/موبایل)، $subLabel --}}
<div class="atm-card {{ $kind }}" data-tilt>
  <span class="atm-shine"></span><span class="atm-orb o1"></span><span class="atm-orb o2"></span>
  <div class="atm-top"><span class="atm-brand"><img src="{{ asset('img/mija-mark.png') }}" alt="" width="26" height="30"> کیف پول می‌جا</span><span class="atm-wave" aria-hidden="true"><i></i><i></i><i></i></span></div>
  <div class="atm-mid">
    <svg class="atm-chip" viewBox="0 0 48 36" aria-hidden="true"><rect x="1" y="1" width="46" height="34" rx="7" fill="url(#cg)" stroke="#8E5018" stroke-opacity=".5"/><defs><linearGradient id="cg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFF3D1"/><stop offset="1" stop-color="#E7B456"/></linearGradient></defs><path d="M1 12h14M1 24h14M33 12h14M33 24h14M15 1v34M33 1v34M15 18h18" stroke="#8E5018" stroke-opacity=".55" fill="none"/></svg>
    <div class="atm-bal"><small>{{ $label }}</small><div><b class="count" data-to="{{ (int) $amount }}">{{ toman($amount, false) }}</b><em>تومان</em></div></div>
  </div>
  <div class="atm-bot"><div><small>صاحب حساب</small><b>{{ $holder }}</b></div><div class="atm-sub"><small>{{ $subLabel }}</small><b class="ltr">{{ $sub }}</b></div></div>
</div>
