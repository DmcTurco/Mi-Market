@extends('company.layouts.base')

@section('title', 'Historial de ' . $product->name)

@section('content')
<x-page-header :title="$product->name" :subtitle="'Entradas y salidas. Hoy quedan ' . qty($product->stock) . '.'">
    <a href="{{ route('company.products.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Productos
    </a>
</x-page-header>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden max-w-3xl">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                <th class="text-left px-5 py-3">Fecha</th>
                <th class="text-left px-4 py-3">Qué pasó</th>
                <th class="text-right px-4 py-3">Cantidad</th>
                <th class="text-right px-5 py-3">Quedaron</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($movements as $movement)
                <tr>
                    <td class="px-5 py-2.5 text-slate-500">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2.5">
                        {{ $movement->reasonLabel() }}
                        @if($movement->notes)<span class="text-xs text-slate-400">· {{ $movement->notes }}</span>@endif
                    </td>
                    <td class="px-4 py-2.5 text-right font-semibold {{ (float) $movement->quantity >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ (float) $movement->quantity >= 0 ? '+' : '−' }}{{ qty(abs((float) $movement->quantity)) }}
                    </td>
                    <td class="px-5 py-2.5 text-right">{{ qty($movement->balance) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">Todavía no hubo movimientos.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($movements->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">{{ $movements->links() }}</div>
    @endif
</div>
@endsection
