@extends('employee.layouts.base')

@section('title', 'Cerrar caja')

@section('content')
<div class="max-w-4xl mx-auto grid lg:grid-cols-2 gap-5">
    <div>
        <h1 class="text-xl font-bold text-slate-800 mb-1">Cerrar caja</h1>
        <p class="text-slate-500 mb-4">Abierta a las {{ $register->opened_at->format('H:i') }}. Esto es lo que pasó en tu turno:</p>
        @include('shared.cash-summary')
    </div>

    <form method="POST" action="{{ route('employee.cash.update') }}"
          x-data="{ counted: @js(old('closing_amount', '')), expected: {{ $summary['expected_cash'] }}, sending: false }"
          @submit="sending = true"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 self-start">
        @csrf
        <div class="w-14 h-14 bg-brand-100 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-coins text-brand-700 text-xl"></i>
        </div>
        <label for="closing_amount" class="block text-lg font-bold text-slate-800 mb-1">Cuenta el dinero del cajón</label>
        <p class="text-sm text-slate-500 mb-4">Billetes y monedas. Escribe el total que contaste.</p>

        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-slate-400">S/</span>
            <input type="text" inputmode="decimal" name="closing_amount" id="closing_amount" x-model="counted" autofocus
                   class="w-full pl-14 pr-4 py-4 text-3xl font-bold border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
        </div>
        @error('closing_amount')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror

        {{-- Cuadre en vivo --}}
        <template x-if="counted !== ''">
            <div class="mt-4 p-4 rounded-xl text-center"
                 :class="Math.abs(counted - expected) < 0.005 ? 'bg-emerald-50 text-emerald-800' : (counted - expected > 0 ? 'bg-sky-50 text-sky-800' : 'bg-red-50 text-red-700')">
                <p x-show="Math.abs(counted - expected) < 0.005" class="text-lg font-bold"><i class="fas fa-circle-check"></i> ¡Cuadra perfecto!</p>
                <p x-show="counted - expected >= 0.005" class="text-lg">Sobran <strong x-text="'S/ ' + (counted - expected).toFixed(2)"></strong></p>
                <p x-show="counted - expected <= -0.005" class="text-lg">Faltan <strong x-text="'S/ ' + (expected - counted).toFixed(2)"></strong></p>
            </div>
        </template>

        <label for="notes" class="block text-sm font-medium text-slate-700 mt-5 mb-1.5">¿Algo que comentar? (opcional)</label>
        <textarea name="notes" id="notes" rows="2" placeholder="Ej. faltan S/ 2 porque di mal un vuelto"
                  class="w-full px-3 py-2 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">{{ old('notes') }}</textarea>

        <button type="submit" :disabled="counted === '' || sending"
                class="mt-5 w-full py-4 bg-brand-600 hover:bg-brand-700 disabled:bg-slate-300 text-white text-lg font-bold rounded-xl">
            <i class="fas fa-lock"></i> Cerrar caja
        </button>
        <p class="text-xs text-slate-400 mt-3 text-center">Después de cerrar ya no podrás vender ni anular ventas de este turno.</p>
    </form>
</div>
@endsection
