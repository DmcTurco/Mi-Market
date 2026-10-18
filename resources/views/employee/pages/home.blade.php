@extends('employee.layouts.base')

@section('title', 'Inicio')

@section('content')
<div>
    <h1 class="text-2xl font-bold text-slate-800">Hola, {{ $employee->name }} 👋</h1>
    <p class="text-slate-500 mt-1 mb-6">{{ $employee->company->name }} · {{ now()->translatedFormat('l d \d\e F') }}</p>

    @if(!$register)
        {{-- Sin caja: un solo camino --}}
        <div class="bg-white rounded-2xl border-2 border-dashed border-brand-500 p-8 text-center">
            <div class="w-20 h-20 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock-open text-brand-700 text-3xl"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800">Para empezar a vender, abre tu caja</h2>
            <p class="text-slate-500 mt-1 mb-6">Solo tienes que contar con cuánto dinero empiezas.</p>
            <a href="{{ route('employee.cash.open') }}"
               class="inline-flex items-center gap-3 px-8 py-4 bg-brand-600 hover:bg-brand-700 text-white text-lg font-bold rounded-xl shadow">
                <i class="fas fa-lock-open"></i> Abrir caja
            </a>
        </div>

        <div class="grid sm:grid-cols-2 gap-4 mt-6">
            <x-action-tile :href="route('employee.products.index')" icon="fa-magnifying-glass-dollar" title="Precios y stock" text="Consulta cuánto cuesta y cuánto queda" />
            <x-action-tile :href="route('employee.purchases.create')" icon="fa-truck-ramp-box" title="Llegó mercadería" text="Registra lo que trajo el proveedor" />
        </div>
    @else
        {{-- Lo vendido hasta ahora --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 flex flex-wrap items-center gap-6 shadow-sm">
            <div>
                <p class="text-sm text-slate-500">Vendiste hoy</p>
                <p class="text-3xl font-bold text-slate-800">{{ money($summary['sales_total']) }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Ventas</p>
                <p class="text-3xl font-bold text-slate-800">{{ $summary['sales_count'] }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Efectivo en caja</p>
                <p class="text-3xl font-bold text-emerald-600">{{ money($summary['expected_cash']) }}</p>
            </div>
            <p class="text-xs text-slate-400 ml-auto">Caja abierta a las {{ $register->opened_at->format('H:i') }}</p>
        </div>

        <x-action-tile :href="route('employee.pos')" icon="fa-cart-shopping" title="Vender" text="Busca o escanea los productos y cobra" primary />

        <div class="grid sm:grid-cols-2 gap-4 mt-4">
            <x-action-tile :href="route('employee.sales.index')" icon="fa-receipt" title="Mis ventas de hoy" text="Reimprimir o anular una venta" />
            <x-action-tile :href="route('employee.clients.index')" icon="fa-handshake" title="Fiados" text="Quién debe y cobrar pagos" />
            <x-action-tile :href="route('employee.cash.movements')" icon="fa-money-bill-transfer" title="Gastos e ingresos" text="Pagaste algo o te dieron dinero" />
            <x-action-tile :href="route('employee.purchases.create')" icon="fa-truck-ramp-box" title="Llegó mercadería" text="Sube el stock de lo que llegó" />
            <x-action-tile :href="route('employee.products.index')" icon="fa-magnifying-glass-dollar" title="Precios y stock" text="Consulta cuánto queda" />
            <x-action-tile :href="route('employee.cash.close')" icon="fa-lock" title="Cerrar caja" text="Al terminar tu turno" />
        </div>
    @endif

    @if($lowStock > 0)
        <a href="{{ route('employee.products.index', ['filtro' => 'pocos']) }}"
           class="mt-6 flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-100">
            <i class="fas fa-triangle-exclamation"></i>
            <span><strong>{{ $lowStock }}</strong> {{ $lowStock === 1 ? 'producto se está' : 'productos se están' }} acabando. Avísale al encargado.</span>
        </a>
    @endif
</div>
@endsection
