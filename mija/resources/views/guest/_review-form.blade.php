    <form class="card" method="post" action="{{ $preview ? '#' : route('guest.bookings.review', $b) }}" @if($preview) onsubmit="return false" @endif enctype="multipart/form-data">@csrf
      <h3><x-i3 name="p_star" class="sm"/> نظر شما درباره‌ی اقامت</h3>
      @include('partials.help', ['title' => 'هدیه‌ی نظر', 'text' => 'نظر شما ابتدا توسط میزبان بررسی می‌شود. پس از انتشار، '.fa(setting('cashback_percent')).'٪ مبلغ رزرو به‌صورت اعتبار به کیف پول شما اضافه می‌شود ('.fa(setting('cashback_days')).' روز قابل استفاده است). متن و عکس‌ها باید محترمانه و مطابق قوانین باشد.'])
      @foreach(['r_clean' => 'پاکیزگی', 'r_accuracy' => 'مطابقت با آگهی', 'r_location' => 'موقعیت', 'r_host' => 'برخورد میزبان', 'r_value' => 'ارزش نسبت به قیمت'] as $k => $l)
        <div class="row between" style="margin-bottom:8px"><span>{{ $l }}</span><select name="{{ $k }}" style="width:auto;padding-block:6px">@foreach([5,4,3,2,1] as $n)<option value="{{ $n }}">{{ fa($n) }} از ۵</option>@endforeach</select></div>
      @endforeach
      <div class="field"><textarea class="input" name="comment" required minlength="10" placeholder="تجربه‌ی خود را بنویسید…">{{ old('comment') }}</textarea>@error('comment')<div class="msg-e">{{ fa($message) }}</div>@enderror</div>
      <label class="check"><input type="checkbox" name="recommend" value="1" checked> این اقامتگاه را به دیگران پیشنهاد می‌کنم</label>
      <div class="field mt"><label>عکس‌های شما (اختیاری، حداکثر ۴)</label><input class="input" type="file" name="photos[]" accept="image/*" multiple>@error('photos.*')<div class="msg-e">{{ fa($message) }}</div>@enderror</div>
      <button class="btn" @if($preview) type="button" @endif>ثبت نظر</button>
    </form>
