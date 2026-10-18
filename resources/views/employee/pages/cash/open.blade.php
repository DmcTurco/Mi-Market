@extends('employee.layouts.base')

@section('title', 'Abrir caja')

@section('content')
<div class="max-w-md mx-auto">
    <form method="POST" action="{{ route('employee.cash.store') }}" x-data="{ sending: false }" @submit="sending = true"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 text-center">
        @csrf
        <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-lock-open text-brand-700 text-2xl"></i>
        </div>
        <h1 class="text-xl font-bold text-slate-800">Abrir caja</h1>
        <p class="text-slate-500 mt-1 mb-6">Cuenta el dinero que hay en el cajón antes de empezar.<br>Si no hay nada, deja 0.</p>

        <label for="opening_amount" class="block text-left text-sm font-medium text-slate-700 mb-2">¿Con cuánto dinero empiezas?</label>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-slate-400">S/</span>
            <input type="text" inputmode="decimal" name="opening_amount" id="opening_amount" autofocus
                   value="{{ old('opening_amount', '0') }}" onfocus="this.select()"
                   class="w-full pl-14 pr-4 py-4 text-3xl font-bold text-slate-800 border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
        </div>
        @error('opening_amount')
            <p class="text-sm text-red-600 mt-2 text-left">{{ $message }}</p>
        @enderror

        <button type="submit" :disabled="sending"
                class="mt-6 w-full py-4 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white text-lg font-bold rounded-xl">
            Abrir caja y empezar
        </button>
    </form>
</div>
@endsection
