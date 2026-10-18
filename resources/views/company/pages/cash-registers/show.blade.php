@extends('company.layouts.base')

@section('title', 'Caja de ' . $register->employee->name)

@section('content')
<x-page-header :title="'Caja de ' . $register->employee->name"
               :subtitle="$register->opened_at->format('d/m/Y H:i') . ($register->isOpen() ? ' · abierta ahora' : ' a ' . $register->closed_at->format('H:i'))">
    <a href="{{ route('company.cash-registers.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Cajas
    </a>
</x-page-header>

<div class="grid lg:grid-cols-3 gap-5">
    <div>
        @include('shared.cash-summary')

        @unless($register->isOpen())
            @php($diff = (float) $register->difference)
            <div @class(['mt-4 p-4 rounded-xl text-center',
                'bg-emerald-50 text-emerald-800' => abs($diff) < 0.005,
                'bg-sky-50 text-sky-800' => $diff >= 0.005,
                'bg-red-50 text-red-700' => $diff <= -0.005])>
                <p class="text-sm">Contó {{ money($register->closing_amount) }}</p>
                <p class="text-xl font-bold">
                    @if(abs($diff) < 0.005) Cuadró perfecto @elseif($diff > 0) Sobró {{ money($diff) }} @else Faltó {{ money(abs($diff)) }} @endif
                </p>
                @if($register->notes)<p class="text-sm mt-2 italic">“{{ $register->notes }}”</p>@endif
            </div>
        @endunless
    </div>

    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100"><h2 class="font-semibold text-slate-800">Ventas ({{ $sales->count() }})</h2></div>
            <div class="max-h-96 overflow-y-auto">
                @forelse($sales as $sale)
                    <a href="{{ route('company.sales.show', $sale) }}" class="px-5 py-2.5 border-b border-slate-100 last:border-0 flex items-center gap-3 text-sm hover:bg-slate-50 {{ $sale->isVoided() ? 'opacity-60' : '' }}">
                        <span class="text-slate-400 w-12">{{ $sale->created_at->format('H:i') }}</span>
                        <span class="flex-1">{{ $sale->voucherNumber() }} · {{ $sale->paymentLabel() }} @if($sale->isVoided())<span class="text-red-600 font-semibold">ANULADA</span>@endif</span>
                        <span class="font-semibold {{ $sale->isVoided() ? 'line-through' : '' }}">{{ money($sale->total) }}</span>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-slate-400">Sin ventas.</p>
                @endforelse
            </div>
        </div>

        @if($movements->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100"><h2 class="font-semibold text-slate-800">Gastos e ingresos</h2></div>
                @foreach($movements as $movement)
                    <div class="px-5 py-2.5 border-b border-slate-100 last:border-0 flex justify-between text-sm">
                        <span><span class="text-slate-400 mr-2">{{ $movement->created_at->format('H:i') }}</span>{{ $movement->concept }}</span>
                        <span class="font-semibold {{ $movement->isExpense() ? 'text-red-600' : 'text-emerald-600' }}">{{ $movement->isExpense() ? '−' : '+' }} {{ money($movement->amount) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if($payments->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100"><h2 class="font-semibold text-slate-800">Pagos de fiados cobrados</h2></div>
                @foreach($payments as $payment)
                    <div class="px-5 py-2.5 border-b border-slate-100 last:border-0 flex justify-between text-sm">
                        <span><span class="text-slate-400 mr-2">{{ $payment->created_at->format('H:i') }}</span>{{ $payment->client->name }} · {{ \App\Models\Sale::PAYMENT_METHODS[$payment->payment_method] ?? '' }}</span>
                        <span class="font-semibold text-emerald-600">{{ money($payment->amount) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
