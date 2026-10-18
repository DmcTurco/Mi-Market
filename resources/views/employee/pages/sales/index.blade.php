@extends('employee.layouts.base')

@section('title', 'Mis ventas de hoy')

@section('content')
<x-page-header title="Mis ventas de hoy" subtitle="Ventas de tu caja abierta. Toca una para reimprimir o anular.">
    <a href="{{ route('employee.pos') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-lg">
        <i class="fas fa-cart-shopping"></i> Vender
    </a>
</x-page-header>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden max-w-3xl">
    @forelse($sales as $sale)
        <a href="{{ route('employee.sales.show', $sale) }}"
           class="px-5 py-4 border-b border-slate-100 last:border-0 flex items-center gap-4 hover:bg-slate-50 {{ $sale->isVoided() ? 'opacity-60' : '' }}">
            <span class="text-sm text-slate-500 w-14">{{ $sale->created_at->format('H:i') }}</span>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-800">{{ $sale->voucherNumber() }}</p>
                <p class="text-xs text-slate-500">{{ $sale->paymentLabel() }}{{ $sale->customer_name ? ' · ' . $sale->customer_name : '' }}</p>
            </div>
            @if($sale->isVoided())
                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded">Anulada</span>
            @endif
            <span class="font-bold text-lg {{ $sale->isVoided() ? 'line-through' : '' }}">{{ money($sale->total) }}</span>
            <i class="fas fa-chevron-right text-slate-300"></i>
        </a>
    @empty
        <div class="py-16 text-center text-slate-400">
            <i class="fas fa-receipt text-4xl mb-3"></i>
            <p>Todavía no vendiste nada en esta caja.</p>
        </div>
    @endforelse
</div>
@endsection
