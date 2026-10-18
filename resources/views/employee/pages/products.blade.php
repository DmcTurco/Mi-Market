@extends('employee.layouts.base')

@section('title', 'Precios y stock')

@section('content')
<x-page-header title="Precios y stock" subtitle="Consulta cuánto cuesta un producto y cuánto queda." />

<x-search-form :action="route('employee.products.index')" placeholder="Nombre o código de barras..." />

<div class="flex gap-2 mb-3 text-sm">
    <a href="{{ route('employee.products.index') }}" @class(['px-3 py-1.5 rounded-full border', 'bg-brand-600 text-white border-brand-600' => request('filtro') !== 'pocos', 'bg-white border-slate-200' => request('filtro') === 'pocos'])>Todos</a>
    <a href="{{ route('employee.products.index', ['filtro' => 'pocos']) }}" @class(['px-3 py-1.5 rounded-full border', 'bg-amber-500 text-white border-amber-500' => request('filtro') === 'pocos', 'bg-white border-slate-200' => request('filtro') !== 'pocos'])>Se están acabando</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @forelse($products as $product)
        <div class="px-5 py-3 border-b border-slate-100 last:border-0 flex items-center gap-4">
            <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-800">{{ $product->name }}</p>
                @if($product->barcode)<p class="text-xs text-slate-400 font-mono">{{ $product->barcode }}</p>@endif
                @foreach($product->presentations as $presentation)
                    <span class="inline-block mt-1 mr-1 px-2 py-0.5 rounded bg-brand-50 text-brand-700 text-xs">{{ $presentation->label() }}: <strong>{{ money($presentation->price) }}</strong></span>
                @endforeach
            </div>
            <span @class(['text-sm px-2 py-1 rounded', 'bg-red-50 text-red-700 font-semibold' => (float) $product->stock <= 0, 'bg-amber-50 text-amber-700' => (float) $product->stock > 0 && $product->isLowStock(), 'text-slate-500' => (float) $product->stock > 0 && !$product->isLowStock()])>
                {{ (float) $product->stock <= 0 ? 'Sin stock' : 'Quedan ' . qty($product->stock) }}
            </span>
            <span class="w-28 text-right text-lg font-bold text-brand-700">
                {{ money($product->sale_price) }}@if($product->isFractional())<span class="block text-xs font-normal text-slate-400">el {{ mb_strtolower($product->unitLabel()) }}</span>@endif
            </span>
        </div>
    @empty
        <p class="py-16 text-center text-slate-400">No hay productos que coincidan.</p>
    @endforelse
</div>

@if($products->hasPages())
    <div class="mt-4">{{ $products->links() }}</div>
@endif
@endsection
