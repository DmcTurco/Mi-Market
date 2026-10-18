@extends('company.layouts.base')

@php
    $editing = isset($product);
    $presentations = old('presentations', $editing
        ? $product->presentations->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'units' => qty($p->units), 'price' => number_format($p->price, 2, '.', ''), 'barcode' => $p->barcode])->all()
        : []);
@endphp

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
          cost: @js(old('purchase_price', $editing && (float) $product->purchase_price ? $product->purchase_price : '')),
          unit: @js(old('unit', $product->unit ?? 'unidad')),
          presentations: @js(array_values($presentations)),
          sending: false,
          get profit() { const p = Number(this.price), c = Number(this.cost); return p > 0 && c > 0 ? p - c : null; },
          addPresentation(name = '', units = '') {
              this.presentations.push({ id: null, name, units, price: '', barcode: '' });
          },
          /** Precio por unidad dentro de la caja, para comparar con el precio suelto */
          perUnit(p) { const u = Number(p.units), pr = Number(p.price); return u > 0 && pr > 0 ? pr / u : null; },
      }"
      @submit="sending = true"
      class="grid xl:grid-cols-2 gap-5 items-start">
    @csrf
    @if($editing) @method('PUT') @endif

    {{-- Columna 1: lo básico --}}
    <div class="space-y-5">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 grid sm:grid-cols-2 gap-4">
            <h2 class="sm:col-span-2 font-semibold text-slate-800">Lo básico</h2>

            <x-field label="Nombre del producto" name="name" required class="sm:col-span-2">
                <x-input name="name" :value="$product->name ?? null" maxlength="150" autofocus placeholder="Ej. Cerveza Pilsen 630ml" class="py-3 text-base" />
            </x-field>

            <x-field label="Se vende por" name="unit" class="sm:col-span-2">
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                    @foreach(\App\Models\Product::UNITS as $key => [$label, , $fractional])
                        <label class="cursor-pointer">
                            <input type="radio" name="unit" value="{{ $key }}" x-model="unit" class="peer sr-only">
                            <span class="block text-center py-2.5 rounded-lg border-2 border-slate-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 font-medium">
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-slate-500 mt-1.5" x-show="unit === 'kg' || unit === 'litro'">
                    <i class="fas fa-scale-balanced"></i> En la caja se escribe lo que marca la balanza (½ kilo, ¼, 0.350...) o "por S/ 2".
                </p>
            </x-field>

            <x-field name="sale_price" required :label="'Precio de venta'" hint="Lo que paga el cliente, con IGV">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">S/</span>
                    <input type="text" inputmode="decimal" name="sale_price" id="sale_price" x-model="price"
                           class="w-full pl-10 pr-16 py-3 text-xl font-bold border-2 rounded-lg focus:outline-none focus:border-brand-500 {{ $errors->has('sale_price') ? 'border-red-400' : 'border-slate-300' }}">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                          x-text="{ unidad: 'c/u', kg: 'el kilo', litro: 'el litro', paquete: 'c/paq', caja: 'c/caja' }[unit]"></span>
                </div>
            </x-field>

            <x-field label="¿Cuántos tienes ahora?" name="stock" :hint="$editing ? 'Si lo cambias, queda anotado como corrección' : 'Si no sabes, déjalo en 0'">
                <div class="relative">
                    <input type="text" inputmode="decimal" name="stock" id="stock" value="{{ old('stock', isset($product) ? qty($product->stock) : '0') }}" onfocus="this.select()"
                           class="w-full px-3 pr-20 py-3 text-xl font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                          x-text="{ unidad: 'unidades', kg: 'kilos', litro: 'litros', paquete: 'paquetes', caja: 'cajas' }[unit]"></span>
                </div>
            </x-field>

            <x-field label="Categoría" name="category_id">
                <select name="category_id" id="category_id" class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg bg-white focus:outline-none focus:border-brand-500">
                    <option value="">Sin categoría</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Código de barras" name="barcode" hint="Haz clic aquí y pasa la pistola">
                <x-input name="barcode" :value="$product->barcode ?? null" maxlength="30" class="font-mono py-3" x-on:keydown.enter.prevent="" />
            </x-field>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 grid sm:grid-cols-3 gap-4">
            <h2 class="sm:col-span-3 font-semibold text-slate-800">Para tu control <span class="font-normal text-slate-400">(opcional)</span></h2>
            <x-field label="¿Cuánto te costó?" name="purchase_price" hint="Por unidad">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">S/</span>
                    <input type="text" inputmode="decimal" name="purchase_price" id="purchase_price" x-model="cost"
                           class="w-full pl-10 pr-3 py-2.5 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                </div>
                <p x-show="profit !== null" class="text-sm mt-1" :class="profit > 0 ? 'text-emerald-600' : 'text-red-600'"
                   x-text="profit > 0 ? 'Ganas S/ ' + profit.toFixed(2) : 'Lo vendes más barato de lo que costó'"></p>
            </x-field>
            <x-field label="Avísame cuando queden" name="min_stock" hint="0 = sin aviso">
                <input type="text" inputmode="decimal" name="min_stock" id="min_stock" value="{{ old('min_stock', isset($product) ? qty($product->min_stock) : '0') }}"
                       class="w-full px-3 py-2.5 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
            </x-field>
            <x-field label="IGV" name="igv_affectation" hint="Si no sabes, «Con IGV»">
                <select name="igv_affectation" id="igv_affectation" class="w-full px-3 py-2.5 border-2 border-slate-300 rounded-lg bg-white">
                    @foreach(\App\Models\Product::IGV_AFFECTATIONS as $key => $label)
                        <option value="{{ $key }}" @selected(old('igv_affectation', $product->igv_affectation ?? '10') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>
    </div>

    {{-- Columna 2: otras formas de venderlo + guardar --}}
    <div class="space-y-5">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Otras formas de venderlo <span class="font-normal text-slate-400">(opcional)</span></h2>
                <p class="text-sm text-slate-500">Ej. la cerveza suelta, en <strong>media caja</strong> (6) o en <strong>caja</strong> (12), cada una con su precio.
                    Al vender una caja se descuentan las 12 del stock.</p>
            </div>

            <template x-for="(p, index) in presentations" :key="index">
                <div class="px-5 py-4 border-b border-slate-100 grid grid-cols-2 sm:grid-cols-12 gap-3 items-end">
                    <input type="hidden" :name="`presentations[${index}][id]`" :value="p.id">
                    <label class="col-span-2 sm:col-span-4 text-sm text-slate-600">Nombre
                        <input type="text" :name="`presentations[${index}][name]`" x-model="p.name" maxlength="50" placeholder="Caja"
                               class="block w-full mt-1 px-3 py-2.5 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                    </label>
                    <label class="sm:col-span-2 text-sm text-slate-600">Trae
                        <input type="text" inputmode="decimal" :name="`presentations[${index}][units]`" x-model="p.units" placeholder="12"
                               class="block w-full mt-1 px-3 py-2.5 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                    </label>
                    <label class="sm:col-span-3 text-sm text-slate-600">Precio S/
                        <input type="text" inputmode="decimal" :name="`presentations[${index}][price]`" x-model="p.price" placeholder="70.00"
                               class="block w-full mt-1 px-3 py-2.5 font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                    </label>
                    <div class="sm:col-span-3 flex items-end gap-2">
                        <label class="flex-1 text-sm text-slate-600">Código
                            <input type="text" :name="`presentations[${index}][barcode]`" x-model="p.barcode" maxlength="30" @keydown.enter.prevent
                                   class="block w-full mt-1 px-3 py-2.5 font-mono text-sm border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                        </label>
                        <button type="button" @click="presentations.splice(index, 1)" title="Quitar"
                                class="w-10 h-11 shrink-0 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600"><i class="fas fa-trash-can"></i></button>
                    </div>
                    <p class="col-span-2 sm:col-span-12 text-xs text-slate-500" x-show="perUnit(p) !== null">
                        Sale a <strong x-text="'S/ ' + perUnit(p).toFixed(2)"></strong> cada una
                        <span x-show="Number(price) > 0 && perUnit(p) < Number(price)" class="text-emerald-600">
                            (ahorra <span x-text="'S/ ' + ((Number(price) - perUnit(p)) * Number(p.units)).toFixed(2)"></span> frente a comprar sueltas)
                        </span>
                    </p>
                </div>
            </template>

            <div class="px-5 py-4 flex flex-wrap gap-2">
                <button type="button" @click="addPresentation('Caja', '')" class="px-3 py-2 rounded-lg border-2 border-dashed border-slate-300 hover:border-brand-500 text-sm font-medium"><i class="fas fa-plus"></i> Caja</button>
                <button type="button" @click="addPresentation('Media caja', '')" class="px-3 py-2 rounded-lg border-2 border-dashed border-slate-300 hover:border-brand-500 text-sm font-medium"><i class="fas fa-plus"></i> Media caja</button>
                <button type="button" @click="addPresentation('Paquete', '')" class="px-3 py-2 rounded-lg border-2 border-dashed border-slate-300 hover:border-brand-500 text-sm font-medium"><i class="fas fa-plus"></i> Paquete</button>
                <button type="button" @click="addPresentation()" class="px-3 py-2 rounded-lg border-2 border-dashed border-slate-300 hover:border-brand-500 text-sm font-medium"><i class="fas fa-plus"></i> Otra</button>
            </div>
            @if($errors->has('presentations.*'))
                <div class="px-5 pb-4 text-sm text-red-600 space-y-1">
                    @foreach(collect($errors->get('presentations.*'))->flatten()->unique() as $message)
                        <p><i class="fas fa-circle-exclamation"></i> {{ $message }}</p>
                    @endforeach
                </div>
            @endif
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
            <button type="submit" :disabled="sending" class="flex-1 py-4 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white text-lg font-bold rounded-xl">
                {{ $editing ? 'Guardar cambios' : 'Guardar producto' }}
            </button>
            @unless($editing)
                <button type="submit" name="another" value="1" :disabled="sending"
                        class="px-5 py-4 bg-white border-2 border-brand-600 text-brand-700 hover:bg-brand-50 font-semibold rounded-xl">
                    Guardar y crear otro
                </button>
            @endunless
        </div>
    </div>
</form>
@endsection
