<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-3xl">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold">{{ config('app.name') }}</h1>
            <p class="text-slate-500 mt-2">Elige cómo quieres ingresar</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['admin',    'Administrador', 'Gestiona las empresas del sistema', 'fa-shield-halved'],
                ['company',  'Empresa',       'Catálogo y empleados de tu tienda', 'fa-store'],
                ['employee', 'Empleado',      'Ventas y stock del día',            'fa-cash-register'],
            ] as [$panel, $label, $text, $icon])
                <a href="{{ url($panel . '/login') }}"
                   class="panel-{{ $panel }} group bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:border-brand-500 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-brand-100 group-hover:bg-brand-600 rounded-xl flex items-center justify-center mb-4 transition-colors">
                        <i class="fas {{ $icon }} text-brand-700 group-hover:text-white text-lg transition-colors"></i>
                    </div>
                    <p class="font-semibold">{{ $label }}</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $text }}</p>
                </a>
            @endforeach
        </div>
    </div>
</body>
</html>
