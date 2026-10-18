@extends('company.layouts.base')

@section('title', 'Inicio')

@section('content')
<div>
    <h1 class="text-2xl font-bold text-slate-800">{{ $company->name }}</h1>
    <p class="text-slate-500 mt-1 mb-6">{{ ucfirst(now()->translatedFormat('l d \d\e F')) }}</p>

    {{-- Primeros pasos: solo mientras falte algo --}}
    @if(!$hasProducts || !$hasEmployees)
        <div class="bg-brand-50 border border-brand-100 rounded-2xl p-5 mb-6">
            <h2 class="font-bold text-slate-800 mb-3">Para empezar a vender</h2>
            <ol class="space-y-2">
                <li class="flex items-center gap-3">
                    <i class="fas {{ $hasProducts ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-300' }}"></i>
                    <a href="{{ route('company.products.create') }}" class="{{ $hasProducts ? 'line-through text-slate-400' : 'text-brand-700 font-medium hover:underline' }}">1. Registra tus productos con su precio</a>
                </li>
                <li class="flex items-center gap-3">
                    <i class="fas {{ $hasEmployees ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-300' }}"></i>
                    <a href="{{ route('company.employees.create') }}" class="{{ $hasEmployees ? 'line-through text-slate-400' : 'text-brand-700 font-medium hover:underline' }}">2. Crea el usuario de tu cajero</a>
                </li>
                <li class="flex items-center gap-3">
                    <i class="fas fa-circle text-slate-300"></i>
                    <span class="text-slate-600">3. El cajero entra en <strong>/employee/login</strong>, abre su caja y vende</span>
                </li>
            </ol>
        </div>
    @endif

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('company.sales.index') }}" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-brand-500">
            <p class="text-sm text-slate-500">Vendido hoy</p>
            <p class="text-3xl font-bold text-slate-800">{{ money($salesToday) }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $countToday }} {{ $countToday === 1 ? 'venta' : 'ventas' }}</p>
        </a>
        <a href="{{ route('company.cash-registers.index') }}" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-brand-500">
            <p class="text-sm text-slate-500">Cajas abiertas</p>
            <p class="text-3xl font-bold text-slate-800">{{ $openRegisters->count() }}</p>
            <p class="text-xs text-slate-400 mt-1 truncate">{{ $openRegisters->pluck('employee.name')->join(', ') ?: 'Nadie está vendiendo ahora' }}</p>
        </a>
        <a href="{{ route('company.clients.index') }}" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-brand-500">
            <p class="text-sm text-slate-500">Te deben (fiados)</p>
            <p class="text-3xl font-bold {{ $debtTotal > 0 ? 'text-amber-700' : 'text-slate-800' }}">{{ money($debtTotal) }}</p>
            <p class="text-xs text-slate-400 mt-1">Toca para ver quién</p>
        </a>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <x-action-tile :href="route('company.products.create')" icon="fa-plus" title="Nuevo producto" text="Nombre, precio y cuánto tienes" />
        <x-action-tile :href="route('company.purchases.create')" icon="fa-truck-ramp-box" title="Llegó mercadería" text="Sube el stock de lo que compraste" />
    </div>

    @if($lowStock->isNotEmpty())
        <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-amber-50 border-b border-amber-200 flex items-center justify-between">
                <h2 class="font-semibold text-amber-800"><i class="fas fa-triangle-exclamation"></i> Se están acabando</h2>
                <a href="{{ route('company.products.index', ['filtro' => 'pocos']) }}" class="text-sm text-amber-800 hover:underline">Ver todos</a>
            </div>
            @foreach($lowStock as $product)
                <a href="{{ route('company.products.edit', $product) }}" class="px-5 py-2.5 border-b border-slate-100 last:border-0 flex justify-between hover:bg-slate-50">
                    <span>{{ $product->name }}</span>
                    <span class="font-semibold {{ (float) $product->stock <= 0 ? 'text-red-600' : 'text-amber-700' }}">
                        {{ (float) $product->stock <= 0 ? 'Sin stock' : 'Quedan ' . qty($product->stock) }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
