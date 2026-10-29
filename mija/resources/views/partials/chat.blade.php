@if(! in_array($b->status, ['expired']))
<div class="card mt"><h3><x-icon name="chat" class="sm"/> گفتگو با {{ auth()->id() === $b->guest_id ? 'میزبان' : 'مهمان' }}</h3>
  <div class="chat">
    @forelse($b->messages as $m)<div class="msg {{ $m->sender_id === auth()->id() ? 'mine' : '' }}">{{ $m->body }}<small>{{ jdate($m->created_at, 'j F H:i') }}</small></div>@empty<div class="muted small">هنوز پیامی رد و بدل نشده است.</div>@endforelse
  </div>
  @php $qr = auth()->id() === $b->guest_id ? ['سلام، ورود زودتر ممکن است؟', 'امکان خروج دیرتر هست؟', 'می‌شود آدرس دقیق را بفرستید؟', 'اینترنت و وای‌فای دارد؟'] : ['خوش آمدید! آماده‌ی پذیرایی هستیم.', 'آدرس و راهنمای ورود را ارسال می‌کنم.', 'ممنون از انتخاب شما، سفر خوبی داشته باشید.', 'لطفاً ساعت تقریبی رسیدن را اعلام کنید.']; @endphp
  <div class="qchips" style="margin-top:10px">@foreach($qr as $t)<button type="button" class="qchip" data-qr="{{ $t }}">{{ $t }}</button>@endforeach</div>
  <form method="post" action="{{ route('booking.message', $b) }}" class="row">@csrf<input class="input" name="body" placeholder="پیام خود را بنویسید…" required maxlength="1000"><button class="btn btn-icon" aria-label="ارسال"><x-icon name="send"/></button></form>
</div>
@endif
