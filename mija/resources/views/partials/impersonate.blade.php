@if(session('impersonator_id'))
<div class="imp-bar"><span><x-icon name="eye" class="sm"/> شما به‌جای «{{ auth()->user()->name }}» ({{ auth()->user()->roleLabel() }}) وارد شده‌اید</span><a class="btn btn-sm btn-dark" href="{{ route('impersonate.leave') }}">بازگشت به حساب مدیر و خروج از این حساب</a></div>
@endif
