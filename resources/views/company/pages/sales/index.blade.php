@extends('company.layouts.base')

@section('title', 'Ventas')

@section('content')
<x-page-header title="Ventas" :subtitle="ucfirst($date->translatedFormat('l d \d\e F \d\e Y'))" />

<form method="GET" action="{{ route('company.sales.index') }}" class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm flex flex-wrap items-end gap-3 mb-4">
    <label class="text-sm text-slate-600">Día
        <input type="date" name="fecha" value="{{ $date->toDateString() }}" onchange="this.form.submit()"
               class="block mt-1 px-3 py-2 border border-slate-300 rounded-lg">
    </label>
    <label class="text-sm text-slate-600">Vendedor
        <select name="empleado" onchange="this.form.submit()" class="block mt-1 px-3 py-2 border border-slate-300 rounded-lg bg-white">
            <option value="">Todos</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" @selected(request('empleado') == $employee->id)>{{ $employee->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm text-slate-600">Pago
        <select name="pago" onchange="this.form.submit()" class="block mt-1 px-3 py-2 border border-slate-300 rounded-lg bg-white">
            <option value="">Todos</option>
            @foreach(\App\Models\Sale::PAYMENT_METHODS as $key => $label)
                <option value="{{ $key }}" @selected(request('pago') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <div class="ml-auto text-right">
        <p class="text-sm text-slate-500">{{ $totals->count }} {{ $totals->count == 1 ? 'venta' : 'ventas' }}</p>
        <p class="text-2xl font-bold text-slate-800">{{ money($totals->total) }}</p>
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @if($sales->isEmpty())
        <div class="py-16 text-center text-slate-400">
            <i class="fas fa-receipt text-4xl mb-3"></i>
            <p>No hay ventas ese día.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Hora</th>
                        <th class="text-left px-4 py-3">Comprobante</th>
                        <th class="text-left px-4 py-3">Vendedor</th>
                        <th class="text-left px-4 py-3">Pago</th>
                        <th class="text-right px-5 py-3">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($sales as $sale)
                        <tr class="hover:bg-slate-50 cursor-pointer {{ $sale->isVoided() ? 'opacity-60' : '' }}" onclick="location.href='{{ route('company.sales.show', $sale) }}'">
                            <td class="px-5 py-3 text-slate-500">{{ $sale->created_at->format('H:i') }}</td>
                            <td class="px-4 py-3 font-medium">
                                {{ $sale->voucherNumber() }}
                                @if($sale->isVoided())<span class="ml-1 text-xs font-semibold text-red-600">ANULADA</span>@endif
                            </td>
                            <td class="px-4 py-3">{{ $sale->employee?->name }}</td>
                            <td class="px-4 py-3">{{ $sale->paymentLabel() }}{{ $sale->customer_name ? ' · ' . $sale->customer_name : '' }}</td>
                            <td class="px-5 py-3 text-right font-bold {{ $sale->isVoided() ? 'line-through' : '' }}">{{ money($sale->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $sales->links() }}</div>
        @endif
    @endif
</div>
@endsection
