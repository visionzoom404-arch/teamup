{{-- شمارش معکوس فارسی با حلقه‌ی پیشرفت. $until: Carbon، $total: ثانیه، $label: عنوان --}}
@php $left = max(0, now()->diffInSeconds($until, false)); @endphp
<div class="cd" data-cd="{{ $until->toIso8601String() }}" data-total="{{ $total }}" data-left="{{ $left }}">
  <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="bg" cx="60" cy="60" r="52"/><circle class="fg" cx="60" cy="60" r="52"/></svg>
  <div class="cd-t"><b data-cd-t>{{ fa(sprintf('%02d:%02d', intdiv($left, 60), $left % 60)) }}</b><small>{{ $label }}</small></div>
</div>
