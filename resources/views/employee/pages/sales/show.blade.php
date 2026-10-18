@extends('employee.layouts.base')

@section('title', 'Venta ' . $sale->voucherNumber())

@section('content')
<x-page-header :title="'Venta ' . $sale->voucherNumber()">
    <a href="{{ route('employee.sales.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Mis ventas
    </a>
</x-page-header>

@include('shared.sale-detail', [
    'ticketUrl' => route('employee.sales.ticket', $sale),
    'voidUrl'   => route('employee.sales.void', $sale),
])
@endsection
