{{--
    Estructura común de los tres paneles. Cada panel la extiende desde su propio layout
    ({panel}/layouts/base) indicando $panel = admin | company | employee:
      - color de marca  → clase panel-{panel} del <body> (ver resources/css/app.css)
      - menú lateral    → {panel}/inc/sidebar
      - usuario         → auth()->guard($panel)
--}}
@php
    $panels = [
        'admin'    => ['label' => 'Panel Admin',    'icon' => 'fa-shield-halved'],
        'company'  => ['label' => 'Panel Empresa',  'icon' => 'fa-store'],
        'employee' => ['label' => 'Panel Empleado', 'icon' => 'fa-cash-register'],
    ];
    $current = $panels[$panel];
    $user    = auth()->guard($panel)->user();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Inicio')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-{{ $panel }} bg-slate-50 text-slate-800 antialiased app-shell overflow-hidden" x-data="panelLayout">

    {{-- Fondo oscuro del menú en móvil --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/50 z-20 lg:hidden"></div>

    {{-- Menú lateral --}}
    <aside class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-slate-200 flex flex-col
                  -translate-x-full lg:translate-x-0 transition-all duration-300"
           :class="{ 'max-lg:translate-x-0': sidebarOpen, 'lg:w-18': collapsed }">

        <div class="flex items-center gap-3 h-16 px-5 border-b border-slate-200 shrink-0"
             :class="{ 'lg:justify-center lg:px-0': collapsed }">
            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center shrink-0">
                <i class="fas {{ $current['icon'] }} text-white text-sm"></i>
            </div>
            <div :class="{ 'lg:hidden': collapsed }">
                <p class="font-bold text-slate-800 text-sm leading-none">{{ config('app.name') }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $current['label'] }}</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto">
            @include($panel . '.inc.sidebar')
        </nav>

        <div class="px-3 py-3 border-t border-slate-200">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Cerrar sesión"
                        class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 transition-colors"
                        :class="{ 'lg:justify-center lg:px-0': collapsed }">
                    <i class="fas fa-right-from-bracket w-4 text-center text-sm"></i>
                    <span :class="{ 'lg:hidden': collapsed }">Cerrar sesión</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Contenido --}}
    <div class="flex flex-col app-shell transition-all duration-300"
         :class="collapsed ? 'lg:ml-18' : 'lg:ml-64'">

        <header class="bg-white border-b border-slate-200 h-16 flex items-center px-4 md:px-6 gap-3 shrink-0">
            <button type="button" @click="sidebarOpen = true" title="Abrir menú"
                    class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                <i class="fas fa-bars"></i>
            </button>
            <button type="button" @click="toggleCollapsed()" title="Plegar o desplegar el menú"
                    class="hidden lg:flex w-9 h-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                <i class="fas fa-bars"></i>
            </button>

            <p class="flex-1 text-sm font-semibold text-slate-800 truncate">@yield('title', 'Inicio')</p>

            <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
                <div class="w-8 h-8 bg-brand-600 rounded-full flex items-center justify-center shrink-0">
                    <span class="text-white text-xs font-semibold">{{ mb_strtoupper(mb_substr($user?->name ?? '?', 0, 1)) }}</span>
                </div>
                <p class="hidden sm:block text-xs font-medium text-slate-700 max-w-40 truncate">{{ $user?->name }}</p>
            </div>
        </header>

        {{-- Una página puede cambiar el área (p. ej. el punto de venta ocupa todo el alto sin relleno) --}}
        <main class="flex-1 min-h-0 @yield('main-class', 'overflow-auto p-4 md:p-6')">
            @yield('content')
        </main>
    </div>

    @include('partials.alert-modal')
    @include('partials.confirm-modal')
</body>
</html>
