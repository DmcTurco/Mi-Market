@extends('company.layouts.base')

@section('title', 'Venta ' . $sale->voucherNumber())

@section('content')
<x-page-header :title="'Venta ' . $sale->voucherNumber()">
    <a href="{{ route('company.sales.index', ['fecha' => $sale->created_at->toDateString()]) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Ventas del día
    </a>
</x-page-header>

@include('shared.sale-detail', [
    'ticketUrl' => route('company.sales.ticket', $sale),
    // Solo se anula mientras la caja de la venta siga abierta
    'voidUrl'   => $sale->cashRegister->isOpen() ? route('company.sales.void', $sale) : null,
])
@endsection
