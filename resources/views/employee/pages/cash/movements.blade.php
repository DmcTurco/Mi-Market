@extends('employee.layouts.base')

@section('title', 'Gastos e ingresos')

@section('content')
<div class="max-w-4xl grid lg:grid-cols-2 gap-5">
    <form method="POST" action="{{ route('employee.cash.movements.store') }}"
          x-data="{ type: @js(old('type', 'expense')), sending: false }" @submit="sending = true"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4 self-start">
        @csrf
        <h1 class="text-xl font-bold text-slate-800">Gastos e ingresos</h1>
        <p class="text-sm text-slate-500 -mt-2">Anota cuando sacas dinero de la caja para pagar algo, o cuando te dan dinero que no es una venta.</p>

        <input type="hidden" name="type" :value="type">
        <div class="grid grid-cols-2 gap-3">
            <button type="button" @click="type = 'expense'"
                    :class="type === 'expense' ? 'bg-red-600 text-white border-red-600' : 'bg-white border-slate-200'"
                    class="py-4 rounded-xl border-2 font-bold"><i class="fas fa-arrow-up-from-bracket"></i><br>Saqué dinero</button>
            <button type="button" @click="type = 'income'"
                    :class="type === 'income' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white border-slate-200'"
                    class="py-4 rounded-xl border-2 font-bold"><i class="fas fa-arrow-down-to-bracket"></i><br>Me dieron dinero</button>
        </div>

        <div>
            <label for="concept" class="block text-sm font-medium text-slate-700 mb-1.5" x-text="type === 'expense' ? '¿En qué lo gastaste?' : '¿De dónde vino?'"></label>
            <x-input name="concept" maxlength="150" ::placeholder="type === 'expense' ? 'Ej. bolsas, flete, almuerzo' : 'Ej. sencillo que dejó el dueño'" />
            @error('concept')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <x-field label="¿Cuánto?" name="amount">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">S/</span>
                <input type="text" inputmode="decimal" name="amount" id="amount" value="{{ old('amount') }}"
                       class="w-full pl-10 pr-3 py-3 text-xl font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
            </div>
        </x-field>

        <button type="submit" :disabled="sending" class="w-full py-3.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white text-lg font-bold rounded-xl">
            Guardar
        </button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden self-start">
        <div class="px-5 py-3 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">En este turno</h2>
        </div>
        @forelse($movements as $movement)
            <div class="px-5 py-3 border-b border-slate-100 last:border-0 flex items-center gap-3">
                <span class="text-sm text-slate-400 w-12">{{ $movement->created_at->format('H:i') }}</span>
                <span class="flex-1">{{ $movement->concept }}</span>
                <span class="font-semibold {{ $movement->isExpense() ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $movement->isExpense() ? '−' : '+' }} {{ money($movement->amount) }}
                </span>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-slate-400">Nada por ahora.</p>
        @endforelse
    </div>
</div>
@endsection
