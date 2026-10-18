@extends('company.layouts.base')

@section('title', 'Configuración')

@section('content')
<x-page-header title="Configuración" subtitle="Reglas para tus cajeros al vender." />

<form method="POST" action="{{ route('company.settings.update') }}"
      x-data="{
          discounts: @js((bool) old('allow_discounts', $company->allow_discounts)),
          rounding: @js((bool) old('cash_rounding', $company->cash_rounding)),
          favor: @js(old('rounding_favor', $company->rounding_favor)),
          max: @js(old('max_discount_percent', $company->max_discount_percent)),
          sending: false,
      }"
      @submit="sending = true"
      class="grid xl:grid-cols-2 gap-5 items-start">
    @csrf
    @method('PUT')

    {{-- Descuentos --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 bg-brand-100 rounded-xl flex items-center justify-center shrink-0"><i class="fas fa-tag text-brand-700"></i></div>
            <div>
                <h2 class="font-semibold text-slate-800">Rebajas y descuentos</h2>
                <p class="text-sm text-slate-500">Cuando el cliente pide "¿me lo deja más barato?". El cajero puede rebajar el precio de un producto o descontar a toda la venta.</p>
            </div>
        </div>

        <input type="hidden" name="allow_discounts" value="0">
        <label class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 cursor-pointer">
            <input type="checkbox" name="allow_discounts" value="1" x-model="discounts" class="w-5 h-5 accent-brand-600">
            <span class="font-medium text-slate-800">Los cajeros pueden hacer rebajas y descuentos</span>
        </label>

        <div x-show="discounts" x-cloak>
            <x-field label="Descuento máximo por venta" name="max_discount_percent" hint="Suma de todas las rebajas y descuentos de una venta">
                <div class="flex items-center gap-2">
                    <input type="text" inputmode="numeric" name="max_discount_percent" id="max_discount_percent" x-model="max"
                           class="w-24 px-3 py-2.5 text-xl font-bold text-center border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                    <span class="text-xl font-bold text-slate-500">%</span>
                    <span class="text-sm text-slate-500" x-show="Number(max) > 0" x-text="'En una venta de S/ 50 pueden descontar hasta S/ ' + (50 * Number(max) / 100).toFixed(2)"></span>
                </div>
            </x-field>
        </div>
    </div>

    {{-- Redondeo --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 bg-brand-100 rounded-xl flex items-center justify-center shrink-0"><i class="fas fa-coins text-brand-700"></i></div>
            <div>
                <h2 class="font-semibold text-slate-800">Redondeo del efectivo</h2>
                <p class="text-sm text-slate-500">Ya no circulan monedas de 1 ni 5 céntimos. Con pago en efectivo se cobra al múltiplo de S/ 0.10.
                    La boleta mantiene el total exacto y la caja cuadra con lo que realmente cobraste.</p>
            </div>
        </div>

        <input type="hidden" name="cash_rounding" value="0">
        <label class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 cursor-pointer">
            <input type="checkbox" name="cash_rounding" value="1" x-model="rounding" class="w-5 h-5 accent-brand-600">
            <span class="font-medium text-slate-800">Redondear los pagos en efectivo</span>
        </label>

        <input type="hidden" name="rounding_favor" :value="favor">
        <div x-show="rounding" x-cloak class="grid sm:grid-cols-2 gap-3">
            <button type="button" @click="favor = 'customer'"
                    :class="favor === 'customer' ? 'border-brand-600 bg-brand-50' : 'border-slate-200'"
                    class="text-left p-4 rounded-xl border-2">
                <p class="font-bold text-slate-800">A favor del cliente <span class="text-xs font-normal text-emerald-600">(recomendado)</span></p>
                <p class="text-sm text-slate-500 mt-1">S/ 4.27 → cobras <strong>S/ 4.20</strong></p>
                <p class="text-xs text-slate-400 mt-1">Es lo que pide la ley de protección al consumidor.</p>
            </button>
            <button type="button" @click="favor = 'business'"
                    :class="favor === 'business' ? 'border-brand-600 bg-brand-50' : 'border-slate-200'"
                    class="text-left p-4 rounded-xl border-2">
                <p class="font-bold text-slate-800">A favor de la tienda</p>
                <p class="text-sm text-slate-500 mt-1">S/ 4.27 → cobras <strong>S/ 4.30</strong></p>
                <p class="text-xs text-slate-400 mt-1">Solo si el cliente está de acuerdo.</p>
            </button>
        </div>
    </div>

    <div class="xl:col-span-2">
        <button type="submit" :disabled="sending" class="w-full sm:w-auto px-10 py-3.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white text-lg font-bold rounded-xl">
            Guardar configuración
        </button>
    </div>
</form>
@endsection
