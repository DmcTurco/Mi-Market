@extends('company.layouts.base')

@php($editing = isset($product))

@section('title', $editing ? 'Editar producto' : 'Nuevo producto')

@section('content')
<x-page-header :title="$editing ? $product->name : 'Nuevo producto'">
    <a href="{{ route('company.products.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Volver
    </a>
</x-page-header>

<form method="POST" action="{{ $editing ? route('company.products.update', $product) : route('company.products.store') }}"
      x-data="{
          price: @js(old('sale_price', $product->sale_price ?? '')),
          cost: @js(old('purchase_price', isset($product) ? (float) $product->purchase_price ?: '' : '')),
          more: @js($errors->hasAny(['purchase_price', 'min_stock', 'igv_affectation', 'barcode'])),
          sending: false,
          get profit() { const p = Number(this.price), c = Number(this.cost); return p > 0 && c > 0 ? p - c : null; },
      }"
      @submit="sending = true"
      class="max-w-2xl space-y-4">
    @csrf
    @if($editing) @method('PUT') @endif

    {{-- Lo básico: nombre, precio y cuánto hay --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 grid sm:grid-cols-2 gap-4">
        <x-field label="Nombre del producto" name="name" required class="sm:col-span-2">
            <x-input name="name" :value="$product->name ?? null" maxlength="150" autofocus placeholder="Ej. Arroz Costeño 1kg" class="py-3 text-base" />
        </x-field>

        <x-field label="Precio de venta" name="sale_price" required hint="Lo que paga el cliente (con IGV)">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">S/</span>
                <input type="text" inputmode="decimal" name="sale_price" id="sale_price" x-model="price"
                       class="w-full pl-10 pr-3 py-3 text-xl font-bold border-2 rounded-lg focus:outline-none focus:border-brand-500 {{ $errors->has('sale_price') ? 'border-red-400' : 'border-slate-300' }}">
            </div>
        </x-field>

        <x-field label="¿Cuántos tienes ahora?" name="stock" :hint="$editing ? 'Si lo cambias, queda anotado como corrección' : 'Si no sabes, déjalo en 0'">
            <input type="text" inputmode="decimal" name="stock" id="stock" value="{{ old('stock', isset($product) ? qty($product->stock) : '0') }}" onfocus="this.select()"
                   class="w-full px-3 py-3 text-xl font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
        </x-field>

        <x-field label="Se vende por" name="unit">
            <select name="unit" id="unit" class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg bg-white focus:outline-none focus:border-brand-500">
                @foreach(\App\Models\Product::UNITS as $key => [$label])
                    <option value="{{ $key }}" @selected(old('unit', $product->unit ?? 'unidad') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <x-field label="Categoría" name="category_id">
            <select name="category_id" id="category_id" class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg bg-white focus:outline-none focus:border-brand-500">
                <option value="">Sin categoría</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </x-field>
    </div>

    {{-- Lo opcional, escondido para no asustar --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <button type="button" @click="more = !more" class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-slate-50">
            <span>
                <span class="block font-semibold text-slate-800">Más datos (opcional)</span>
                <span class="block text-sm text-slate-500">Código de barras, costo, aviso de stock bajo, IGV</span>
            </span>
            <i class="fas text-slate-400" :class="more ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
        <div x-show="more" x-cloak class="px-5 pb-5 pt-1 grid sm:grid-cols-2 gap-4 border-t border-slate-100">
            <x-field label="Código de barras" name="barcode" hint="Pasa la pistola sobre este campo" class="sm:col-span-2 pt-4">
                <x-input name="barcode" :value="$product->barcode ?? null" maxlength="30" class="font-mono" x-on:keydown.enter.prevent="" />
            </x-field>
            <x-field label="¿Cuánto te costó?" name="purchase_price" hint="Por unidad. Sirve para saber cuánto ganas.">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">S/</span>
                    <input type="text" inputmode="decimal" name="purchase_price" id="purchase_price" x-model="cost"
                           class="w-full pl-10 pr-3 py-2.5 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                </div>
                <p x-show="profit !== null" class="text-sm mt-1" :class="profit > 0 ? 'text-emerald-600' : 'text-red-600'"
                   x-text="profit > 0 ? 'Ganas S/ ' + profit.toFixed(2) + ' por cada uno' : 'Ojo: lo vendes más barato de lo que te costó'"></p>
            </x-field>
            <x-field label="Avísame cuando queden" name="min_stock" hint="Deja 0 si no quieres aviso">
                <input type="text" inputmode="decimal" name="min_stock" id="min_stock" value="{{ old('min_stock', isset($product) ? qty($product->min_stock) : '0') }}"
                       class="w-full px-3 py-2.5 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
            </x-field>
            <x-field label="IGV" name="igv_affectation" hint="Si no sabes, deja «Con IGV»" class="sm:col-span-2">
                <select name="igv_affectation" id="igv_affectation" class="w-full px-3 py-2.5 border-2 border-slate-300 rounded-lg bg-white">
                    @foreach(\App\Models\Product::IGV_AFFECTATIONS as $key => $label)
                        <option value="{{ $key }}" @selected(old('igv_affectation', $product->igv_affectation ?? '10') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>
    </div>

    @if($editing)
        <label class="flex items-center gap-3 p-4 bg-white rounded-xl border border-slate-200 cursor-pointer">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" @checked(old('active', $product->active)) class="w-5 h-5 accent-brand-600">
            <span>
                <span class="block font-medium text-slate-800">Se vende</span>
                <span class="block text-sm text-slate-500">Desmárcalo para que no aparezca en la caja (sin borrarlo).</span>
            </span>
        </label>
    @endif

    <div class="flex flex-wrap gap-3">
        <button type="submit" :disabled="sending" class="flex-1 py-3.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white text-lg font-bold rounded-xl">
            {{ $editing ? 'Guardar cambios' : 'Guardar producto' }}
        </button>
        @unless($editing)
            <button type="submit" name="another" value="1" :disabled="sending"
                    class="px-5 py-3.5 bg-white border-2 border-brand-600 text-brand-700 hover:bg-brand-50 font-semibold rounded-xl">
                Guardar y crear otro
            </button>
        @endunless
    </div>
</form>
@endsection
