{{-- نوار مراحل رزرو (انیمیشنی). $steps: [[عنوان، آیکن]]، $at: مرحله‌ی جاری (۱..n؛ n+1 = همه انجام‌شده)، $bad: مرحله‌ی جاری شکست خورده --}}
@php $n = count($steps); $bad = $bad ?? false; @endphp
<ol class="stp" style="--n:{{ $n }};--at:{{ min($at, $n) }}" aria-label="مراحل رزرو">
  @foreach($steps as $i => [$t, $ic])
    @php $k = $i + 1; $cls = $bad && $k === $at ? 'bad' : ($k < $at ? 'done' : ($k === $at ? 'now' : '')); @endphp
    <li class="{{ $cls }}" style="--i:{{ $i }}">
      <i>@if($cls === 'done')<x-icon name="check" class="sm"/>@elseif($cls === 'bad')<x-icon name="x" class="sm"/>@else<x-icon :name="$ic" class="sm"/>@endif</i>
      <b>{{ $t }}</b>
      <small>@if($cls === 'done')انجام شد@elseif($cls === 'now')الان@elseif($cls === 'bad')ناموفق@else{{ 'مرحله '.fa($k) }}@endif</small>
    </li>
  @endforeach
</ol>
