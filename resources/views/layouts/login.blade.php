{{--
    Pantalla de login común. Cada panel la extiende con sus textos:
    $panel, $title, $heading, $intro, $icon, $sideTitle, $sideText, $features = [[ícono, texto], ...]
    El formulario va a route('login'), que Fortify registra con el prefijo del panel (/admin/login...).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — {{ $title }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-touch-input class="panel-{{ $panel }} bg-slate-50 text-slate-800 antialiased">
<div class="min-h-screen flex">

    <div class="flex-1 flex items-center justify-center p-8">
        <div class="w-full max-w-sm">

            <a href="{{ url('/') }}" class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center">
                    <i class="fas {{ $icon }} text-white text-lg"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-lg leading-none">{{ config('app.name') }}</p>
                    <p class="text-xs text-slate-400">{{ $title }}</p>
                </div>
            </a>

            <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ $heading }}</h1>
            <p class="text-slate-500 text-sm mb-8">{{ $intro }}</p>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <p class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Contraseña</label>
                    <x-password-input name="password" required autocomplete="current-password" class="py-2.5" />
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 accent-brand-600">
                    Recordar sesión
                </label>
                <button type="submit" :disabled="sending"
                        class="w-full bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-spinner fa-spin" x-show="sending" x-cloak></i>
                    Ingresar
                </button>
            </form>
        </div>
    </div>

    <div class="hidden lg:flex w-[440px] bg-brand-600 flex-col items-center justify-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full border-2 border-white"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full border-2 border-white"></div>
        </div>
        <div class="relative text-center text-white">
            <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center mx-auto mb-8">
                <i class="fas {{ $icon }} text-white text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold mb-3">{{ $sideTitle }}</h2>
            <p class="text-white/80 text-sm mb-10 leading-relaxed">{{ $sideText }}</p>
            <div class="grid grid-cols-3 gap-3">
                @foreach ($features as [$featureIcon, $featureText])
                    <div class="bg-white/10 rounded-xl p-4">
                        <i class="fas {{ $featureIcon }} text-2xl mb-2 block"></i>
                        <p class="text-xs text-white/80">{{ $featureText }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
</body>
</html>
