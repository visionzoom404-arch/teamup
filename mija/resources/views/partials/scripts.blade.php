<div class="install glass" id="install"><img src="{{ asset('icons/icon-192.png') }}" width="44" height="44" alt=""><div class="grow"><b>نصب اپلیکیشن می‌جا</b><div class="xs muted">دسترسی سریع‌تر از صفحه اصلی گوشی</div></div><button class="btn btn-sm" id="install-btn" type="button">نصب</button><button class="btn btn-sm btn-icon btn-light" id="install-x" type="button" aria-label="بستن"><x-icon name="x" class="sm"/></button></div>
<script src="{{ asset('vendor/gsap.min.js') }}"></script>
<script src="{{ asset('vendor/ScrollTrigger.min.js') }}"></script>
<script src="{{ asset('vendor/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('js/jalali.js') }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('vendor')
<script src="{{ asset('js/fx.js') }}?v={{ filemtime(public_path('js/fx.js')) }}"></script>
@stack('scripts')
