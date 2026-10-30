{{-- جعبه‌ی راهنما. $title، $text (و اختیاری $icon) --}}
<details class="hint" @if(!empty($open)) open @endif>
  <summary><x-i3 name="{{ $icon ?? 'p_compass' }}" class="sm"/> {{ $title }}</summary>
  <div class="hint-b">{!! $text !!}</div>
</details>
