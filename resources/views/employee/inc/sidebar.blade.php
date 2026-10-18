{{-- Sin caja abierta solo se muestra lo que se puede hacer sin ella --}}
@php($hasRegister = auth()->guard('employee')->user()->openCashRegister() !== null)

<x-nav-link :href="route('employee.home')" icon="fa-house" :active="request()->routeIs('employee.home')">Inicio</x-nav-link>

@if($hasRegister)
    <x-nav-heading>Caja</x-nav-heading>
    <x-nav-link :href="route('employee.pos')" icon="fa-cart-shopping" :active="request()->routeIs('employee.pos')">Vender</x-nav-link>
    <x-nav-link :href="route('employee.sales.index')" icon="fa-receipt" :active="request()->routeIs('employee.sales.*')">Mis ventas de hoy</x-nav-link>
    <x-nav-link :href="route('employee.clients.index')" icon="fa-handshake" :active="request()->routeIs('employee.clients.*')">Fiados</x-nav-link>
    <x-nav-link :href="route('employee.cash.movements')" icon="fa-money-bill-transfer" :active="request()->routeIs('employee.cash.movements')">Gastos e ingresos</x-nav-link>
    <x-nav-link :href="route('employee.cash.close')" icon="fa-lock" :active="request()->routeIs('employee.cash.close')">Cerrar caja</x-nav-link>
@else
    <x-nav-heading>Caja</x-nav-heading>
    <x-nav-link :href="route('employee.cash.open')" icon="fa-lock-open" :active="request()->routeIs('employee.cash.open')">Abrir caja</x-nav-link>
@endif

<x-nav-heading>Tienda</x-nav-heading>
<x-nav-link :href="route('employee.purchases.create')" icon="fa-truck-ramp-box" :active="request()->routeIs('employee.purchases.*')">Llegó mercadería</x-nav-link>
<x-nav-link :href="route('employee.products.index')" icon="fa-magnifying-glass-dollar" :active="request()->routeIs('employee.products.*')">Precios y stock</x-nav-link>
