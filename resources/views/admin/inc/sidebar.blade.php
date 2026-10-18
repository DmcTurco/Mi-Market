<x-nav-heading>Administración</x-nav-heading>
<x-nav-link :href="route('admin.home')" icon="fa-house" :active="request()->routeIs('admin.home')">Inicio</x-nav-link>

<x-nav-heading>Sistema</x-nav-heading>
<x-nav-link :href="route('admin.companies.index')" icon="fa-building" :active="request()->routeIs('admin.companies.*')">Empresas</x-nav-link>
