<x-nav-link :href="route('company.home')" icon="fa-house" :active="request()->routeIs('company.home')">Inicio</x-nav-link>

<x-nav-heading>Lo que vendes</x-nav-heading>
<x-nav-link :href="route('company.products.index')" icon="fa-boxes-stacked" :active="request()->routeIs('company.products.*')">Productos</x-nav-link>
<x-nav-link :href="route('company.categories.index')" icon="fa-tags" :active="request()->routeIs('company.categories.*')">Categorías</x-nav-link>
<x-nav-link :href="route('company.purchases.index')" icon="fa-truck-ramp-box" :active="request()->routeIs('company.purchases.*')">Llegó mercadería</x-nav-link>

<x-nav-heading>Dinero</x-nav-heading>
<x-nav-link :href="route('company.sales.index')" icon="fa-receipt" :active="request()->routeIs('company.sales.*')">Ventas</x-nav-link>
<x-nav-link :href="route('company.cash-registers.index')" icon="fa-cash-register" :active="request()->routeIs('company.cash-registers.*')">Cajas</x-nav-link>
<x-nav-link :href="route('company.clients.index')" icon="fa-handshake" :active="request()->routeIs('company.clients.*')">Fiados</x-nav-link>

<x-nav-heading>Personal</x-nav-heading>
<x-nav-link :href="route('company.employees.index')" icon="fa-users" :active="request()->routeIs('company.employees.*')">Empleados</x-nav-link>
<x-nav-link :href="route('company.audit.index')" icon="fa-clipboard-list" :active="request()->routeIs('company.audit.*')">Bitácora</x-nav-link>
<x-nav-link :href="route('company.settings.edit')" icon="fa-gear" :active="request()->routeIs('company.settings.*')">Configuración</x-nav-link>
