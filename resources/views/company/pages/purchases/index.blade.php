@extends('company.layouts.base')

@section('title', 'Llegó mercadería')

@section('content')
<x-page-header title="Llegó mercadería" subtitle="Cada vez que llega mercadería, el stock sube solo.">
    <a href="{{ route('company.purchases.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg shadow-sm">
        <i class="fas fa-plus text-xs"></i> Registrar mercadería
    </a>
</x-page-header>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @forelse($purchases as $purchase)
        <a href="{{ route('company.purchases.show', $purchase) }}" class="px-5 py-4 border-b border-slate-100 last:border-0 flex items-center gap-4 hover:bg-slate-50">
            <div class="w-10 h-10 bg-brand-100 rounded-lg flex items-center justify-center shrink-0">
                <i class="fas fa-truck-ramp-box text-brand-700"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-800">{{ $purchase->supplier ?: 'Sin proveedor' }}</p>
                <p class="text-xs text-slate-500">{{ $purchase->created_at->format('d/m/Y H:i') }} · {{ $purchase->items_count }} {{ $purchase->items_count === 1 ? 'producto' : 'productos' }} · {{ $purchase->employee?->name ?? 'La empresa' }}</p>
            </div>
            <span class="font-bold">{{ money($purchase->total) }}</span>
            <i class="fas fa-chevron-right text-slate-300"></i>
        </a>
    @empty
        <div class="py-16 text-center text-slate-400">
            <i class="fas fa-truck-ramp-box text-4xl mb-3"></i>
            <p>Todavía no registraste mercadería.</p>
        </div>
    @endforelse
</div>

@if($purchases->hasPages())
    <div class="mt-4">{{ $purchases->links() }}</div>
@endif
@endsection
