@extends('company.layouts.base')

@section('title', 'Productos')

@section('content')
<x-page-header title="Productos" subtitle="Todo lo que vendes, con su precio y cuánto te queda.">
    <a href="{{ route('company.products.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg shadow-sm">
        <i class="fas fa-plus text-xs"></i> Nuevo producto
    </a>
</x-page-header>

<form method="GET" action="{{ route('company.products.index') }}" class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm flex flex-wrap gap-3 mb-3">
    <div class="flex-1 min-w-60 relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código de barras..."
               class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <select name="categoria" onchange="this.form.submit()" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
        <option value="">Todas las categorías</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected(request('categoria') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <input type="hidden" name="filtro" value="{{ request('filtro') }}">
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-search text-xs"></i> Buscar</button>
</form>

<div class="flex gap-2 mb-3 text-sm">
    <a href="{{ route('company.products.index') }}" @class(['px-3 py-1.5 rounded-full border', 'bg-brand-600 text-white border-brand-600' => !request('filtro'), 'bg-white border-slate-200' => request('filtro')])>Todos</a>
    <a href="{{ route('company.products.index', ['filtro' => 'pocos']) }}" @class(['px-3 py-1.5 rounded-full border', 'bg-amber-500 text-white border-amber-500' => request('filtro') === 'pocos', 'bg-white border-slate-200' => request('filtro') !== 'pocos'])>
        Se están acabando @if($lowCount)<span class="ml-1 font-bold">{{ $lowCount }}</span>@endif
    </a>
    <a href="{{ route('company.products.index', ['filtro' => 'inactivos']) }}" @class(['px-3 py-1.5 rounded-full border', 'bg-slate-600 text-white border-slate-600' => request('filtro') === 'inactivos', 'bg-white border-slate-200' => request('filtro') !== 'inactivos'])>No se venden</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @if($products->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-boxes-stacked text-slate-300 text-4xl mb-3"></i>
            <p class="text-slate-500">{{ request()->hasAny(['buscar', 'categoria', 'filtro']) ? 'Ningún producto coincide.' : 'Todavía no tienes productos.' }}</p>
            @unless(request()->hasAny(['buscar', 'categoria', 'filtro']))
                <a href="{{ route('company.products.create') }}" class="inline-block mt-4 px-5 py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Registrar el primero</a>
            @endunless
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Producto</th>
                        <th class="text-left px-4 py-3">Categoría</th>
                        <th class="text-right px-4 py-3">Precio</th>
                        <th class="text-right px-4 py-3">Quedan</th>
                        <th class="text-center px-5 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($products as $product)
                        <tr class="hover:bg-slate-50 {{ $product->active ? '' : 'opacity-60' }}">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-800">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $product->unitLabel() }}{{ $product->barcode ? ' · ' . $product->barcode : '' }}{{ $product->active ? '' : ' · no se vende' }}
                                </p>
                                @foreach($product->presentations as $presentation)
                                    <span class="inline-block mt-1 mr-1 px-2 py-0.5 rounded bg-brand-50 text-brand-700 text-xs">{{ $presentation->label() }}: {{ money($presentation->price) }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-800">{{ money($product->sale_price) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span @class(['px-2 py-0.5 rounded', 'bg-red-50 text-red-700 font-semibold' => (float) $product->stock <= 0, 'bg-amber-50 text-amber-700 font-semibold' => (float) $product->stock > 0 && $product->isLowStock()])>
                                    {{ qty($product->stock) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('company.products.edit', $product) }}" title="Editar"
                                       class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-brand-50 hover:text-brand-700"><i class="fas fa-pen text-xs"></i></a>
                                    <a href="{{ route('company.products.history', $product) }}" title="Ver entradas y salidas"
                                       class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-brand-50 hover:text-brand-700"><i class="fas fa-clock-rotate-left text-xs"></i></a>
                                    <form method="POST" action="{{ route('company.products.destroy', $product) }}"
                                          @submit.prevent="$store.confirm.ask($el, @js("Se eliminará «{$product->name}». Las ventas anteriores no se borran."), '¿Eliminar producto?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Eliminar" class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-red-50 hover:text-red-600"><i class="fas fa-trash-can text-xs"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $products->links() }}</div>
        @endif
    @endif
</div>
@endsection
