@extends('layouts.panel')
@section('title', 'پرداخت‌ها')
@section('content')
<div class="page-h"><h1>پرداخت‌ها</h1></div>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>تاریخ</th><th>کاربر</th><th>بابت</th><th>مبلغ</th><th>درگاه</th><th>کد پیگیری</th><th>وضعیت</th></tr></thead><tbody>
@foreach($items as $p)<tr><td class="small nowrap">{{ jdate($p->created_at, 'Y/m/d H:i') }}</td><td>{{ $p->user->name }}</td><td>{{ $p->purpose === 'topup' ? 'شارژ کیف پول' : ($p->booking?->code) }}</td><td>{{ toman($p->amount, false) }}</td><td>{{ $p->gateway }}</td><td class="ltr">{{ $p->ref_id }}</td><td><span class="badge {{ ['paid'=>'green','pending'=>'gold','failed'=>'red','refunded'=>'blue'][$p->status] }}">{{ ['paid'=>'موفق','pending'=>'در انتظار','failed'=>'ناموفق','refunded'=>'بازگشت'][$p->status] }}</span></td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
