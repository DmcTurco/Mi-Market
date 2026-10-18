{{-- Formulario "Llegó mercadería". Variables: $products, $action, $productsUrl (crear producto, o null), $canPayFromCash --}}
<form method="POST" action="{{ $action }}"
      x-data="purchaseForm(@js(['products' => $products, 'old' => old('items')]))" @submit="sending = true"
      class="space-y-4">
    @csrf

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <label class="block text-sm font-medium text-slate-700 mb-2">1. Busca el producto que llegó</label>
        <div class="relative">
            <i class="fas fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none"></i>
            <input type="text" x-model="search" @keydown.enter.prevent="submitSearch()" autofocus autocomplete="off"
                   placeholder="Escribe el nombre o pasa el código de barras..."
                   class="w-full pl-12 pr-4 py-3.5 text-lg border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
        </div>
        <div x-show="results.length" class="mt-2 border border-slate-200 rounded-xl divide-y divide-slate-100 overflow-hidden">
            <template x-for="product in results" :key="product.id">
                <button type="button" @click="add(product)" class="w-full px-4 py-3 text-left hover:bg-brand-50 flex items-center gap-3">
                    <span class="flex-1 font-medium" x-text="product.name"></span>
                    <span class="text-sm text-slate-400" x-text="'Hay ' + product.stock"></span>
                </button>
            </template>
        </div>
        <p x-show="notice" x-cloak class="mt-2 text-sm text-amber-700" x-text="notice"></p>
        @if($productsUrl)
            <p class="mt-2 text-sm text-slate-500">¿Es un producto nuevo? <a href="{{ $productsUrl }}" class="text-brand-700 font-medium hover:underline">Créalo primero aquí</a>.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <p class="px-5 py-3 border-b border-slate-100 text-sm font-medium text-slate-700">2. Escribe cuántos llegaron y a cuánto los compraste</p>

        <template x-for="(item, index) in items" :key="item.product_id + ':' + item.presentation_id">
            <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center gap-3">
                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                <div class="flex-1 min-w-48">
                    <p class="font-semibold text-slate-800" x-text="item.name"></p>
                    <p class="text-xs text-slate-400" x-text="'Ahora hay ' + item.stock + ' · se vende por ' + item.unit.toLowerCase()"></p>
                </div>
                <label class="text-sm text-slate-500">
                    Llegaron
                    <input type="text" inputmode="decimal" :id="`qty-${item.product_id}-${item.presentation_id || 0}`" :name="`items[${index}][quantity]`" x-model="item.quantity"
                           class="block w-24 mt-1 px-3 py-2 text-lg font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                </label>
                {{-- Por cajas: 3 cajas de 12 = 36 unidades --}}
                <label class="text-sm text-slate-500" x-show="item.presentations.length">
                    En
                    <select :name="`items[${index}][presentation_id]`" x-model="item.presentation_id" @change="changePresentation(item)"
                            class="block mt-1 px-3 py-2.5 border-2 border-slate-300 rounded-lg bg-white">
                        <option value="" x-text="plural(item.unit)"></option>
                        <template x-for="p in item.presentations" :key="p.id">
                            <option :value="p.id" :selected="p.id === item.presentation_id" x-text="p.name.toLowerCase() + ' de ' + p.units"></option>
                        </template>
                    </select>
                    <span class="block text-xs text-brand-700 mt-0.5" x-show="item.presentation_id !== '' && baseUnits(item) > 0" x-text="'= ' + baseUnits(item) + ' ' + plural(item.unit)"></span>
                </label>
                <label class="text-sm text-slate-500">
                    {{-- Por cajas se escribe lo que costó UNA CAJA; debajo se ve cuánto sale cada unidad --}}
                    <span :class="item.presentation_id !== '' ? 'font-bold text-brand-700' : ''"
                          x-text="item.presentation_id !== '' ? 'Costo de 1 ' + presentationName(item) + ' (S/)' : 'Costo c/u (S/)'"></span>
                    <input type="text" inputmode="decimal" :name="`items[${index}][unit_cost]`" x-model="item.unit_cost"
                           :class="looksLikeUnitCost(item) ? 'border-amber-500 bg-amber-50' : 'border-slate-300'"
                           class="block w-32 mt-1 px-3 py-2 text-lg border-2 rounded-lg focus:outline-none focus:border-brand-500">
                    <span class="block text-xs text-slate-500 mt-0.5" x-show="item.presentation_id !== '' && costPerUnit(item) !== null"
                          x-text="'= S/ ' + (costPerUnit(item) ?? 0).toFixed(2) + ' cada ' + item.unit.toLowerCase()"></span>
                </label>
                <p class="w-24 text-right font-semibold" x-text="money(lineTotal(item))"></p>
                <p x-show="looksLikeUnitCost(item)" class="basis-full text-sm text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                    <i class="fas fa-triangle-exclamation"></i>
                    ¿Seguro? Escribiste <strong x-text="'S/ ' + item.unit_cost"></strong> por <strong x-text="presentationName(item)"></strong>,
                    pero una sola unidad se vende a <strong x-text="money(item.price)"></strong>.
                    Si es lo que costó <em>una</em> unidad, escribe el costo de la <span x-text="presentationName(item)"></span> completa
                    (<span x-text="'S/ ' + (toNumber(item.unit_cost) * unitsOf(item)).toFixed(2)"></span>).
                </p>
                <button type="button" @click="remove(item)" title="Quitar" class="w-9 h-9 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        </template>

        <p x-show="!items.length" class="px-5 py-10 text-center text-slate-400">Todavía no agregaste productos.</p>

        <div x-show="items.length" class="px-5 py-3 bg-slate-50 flex justify-end items-center gap-3">
            <span class="text-slate-500">Total de la compra</span>
            <span class="text-2xl font-bold" x-text="money(total)"></span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
        <x-field label="3. ¿Quién la trajo? (opcional)" name="supplier">
            <x-input name="supplier" placeholder="Ej. Distribuidora Lima, Don Pedro..." maxlength="150" />
        </x-field>

        @if($canPayFromCash)
            <label class="flex items-start gap-3 p-3 rounded-lg bg-slate-50 cursor-pointer">
                <input type="checkbox" name="paid_from_cash" value="1" @checked(old('paid_from_cash')) class="mt-1 w-5 h-5 accent-brand-600">
                <span>
                    <span class="block font-medium text-slate-800">La pagué con dinero de mi caja</span>
                    <span class="block text-sm text-slate-500">El total se descuenta del efectivo de tu caja como gasto.</span>
                </span>
            </label>
        @endif

        <button type="submit" :disabled="!items.length || sending"
                class="w-full py-4 bg-brand-600 hover:bg-brand-700 disabled:bg-slate-300 text-white text-lg font-bold rounded-xl">
            <i class="fas fa-check"></i> Guardar y subir el stock
        </button>
    </div>
</form>
