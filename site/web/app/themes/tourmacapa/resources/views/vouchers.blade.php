@extends('layouts.app')

@section('content')
<div class="woocommerce-MyAccount-content">
<table class="voucher-table">
<thead>
<tr>
<th>Code</th>
<th>Amount</th>
<th>QR Code</th>
<th>Status</th>
</tr>
</thead>
<tbody>
@foreach($vouchers as $voucher)
<tr>
<td>{{ $voucher->code }}</td>
<td>R$ {{ number_format($voucher->amount, 2, ',', '.') }}</td>
<td><img src="{{ $voucher->qr_path }}" width="100"></td>
<td>
<span class="voucher-status voucher-status-{{ $voucher->status }}">
{{ ucfirst($voucher->status) }}
</span>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
