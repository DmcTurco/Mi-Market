@extends('employee.layouts.base')

@section('title', 'Vender')
@section('main-class', 'overflow-hidden')

@section('content')
<div x-data="pos(@js([
        'products'   => $products,
        'categories' => $categories,
        'vouchers'   => $vouchers,
        'settings'   => $settings,
        'methods'    => \App\Models\Sale::PAYMENT_METHODS,
        'urls'       => [
            'store'   => route('employee.sales.store'),
            'clients' => route('employee.clients.search'),
        ],
     ]))"
     @keydown.window.f2.prevent="focusSearch()"
     class="h-full flex flex-col lg:flex-row">

    {{-- ── Izquierda: buscar y elegir productos ─────────────────────────── --}}
    {{-- min-w-0: sin esto la fila de categorías (con scroll) empuja la pantalla hacia la derecha --}}
    <section class="flex-1 min-w-0 min-h-0 flex flex-col p-3 md:p-4 gap-3">
        <form @submit.prevent="submitSearch()" class="relative shrink-0">
            <i class="fas fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none"></i>
            <input type="text" x-ref="search" x-model="search" autocomplete="off"
                   placeholder="Escribe el nombre o pasa el código de barras..."
                   class="w-full pl-12 pr-12 [@media(pointer:coarse)]:pr-24 py-4 text-lg bg-white border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500 shadow-sm">
            <button type="button" x-show="search" x-cloak @click="search = ''; focusSearch()" title="Borrar"
                    class="absolute right-3 [@media(pointer:coarse)]:right-16 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full text-slate-400 hover:bg-slate-100">
                <i class="fas fa-xmark"></i>
            </button>
        </form>

        {{-- Aviso corto (no encontrado, sin stock...) --}}
        <div x-show="notice" x-cloak x-transition
             class="shrink-0 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-2">
            <i class="fas fa-circle-info"></i><span x-text="notice"></span>
        </div>

        {{-- Categorías --}}
        <div x-show="!search && categories.length" class="shrink-0 flex gap-2 overflow-x-auto pb-1">
            <button type="button" @click="category = null"
                    :class="category === null ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-600 border-slate-200'"
                    class="px-4 py-2 rounded-full border text-sm font-medium whitespace-nowrap">Todos</button>
            <template x-for="cat in categories" :key="cat.id">
                <button type="button" @click="category = cat.id" x-text="cat.name"
                        :class="category === cat.id ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-600 border-slate-200'"
                        class="px-4 py-2 rounded-full border text-sm font-medium whitespace-nowrap"></button>
            </template>
        </div>

        {{-- Productos --}}
        <div class="flex-1 min-h-0 overflow-y-auto">
            {{-- Tantas columnas como entren (mínimo 9.5rem cada tarjeta) --}}
            <div class="grid grid-cols-[repeat(auto-fill,minmax(9.5rem,1fr))] gap-3">
                <template x-for="product in results" :key="product.id">
                    <button type="button" @click="add(product)"
                            :class="product.stock <= 0 ? 'opacity-50' : 'hover:border-brand-500 hover:shadow-md'"
                            class="text-left bg-white rounded-xl border-2 border-slate-200 p-3 transition flex flex-col min-h-28">
                        <span class="text-sm font-semibold text-slate-800 leading-snug line-clamp-2" x-text="product.name"></span>
                        <span class="mt-auto pt-2 text-xl font-bold text-brand-700">
                            <span x-text="money(product.price)"></span><span class="text-xs font-medium text-slate-400" x-show="product.fractional" x-text="' el ' + product.unit.toLowerCase()"></span>
                        </span>
                        <span class="text-xs text-brand-700" x-show="product.presentations.length"
                              x-text="'También ' + product.presentations.map(p => p.name.toLowerCase()).join(', ')"></span>
                        <span class="text-xs" :class="product.stock <= 0 ? 'text-red-500 font-semibold' : 'text-slate-400'"
                              x-text="product.stock <= 0 ? 'Sin stock' : 'Quedan ' + qtyLabel(product.stock) + (product.fractional ? ' ' + product.unit.toLowerCase() : '')"></span>
                    </button>
                </template>
            </div>

            <div x-show="!results.length" class="text-center py-16 text-slate-400">
                <i class="fas fa-box-open text-4xl mb-3"></i>
                <p x-show="search">No hay productos con ese nombre.</p>
                <p x-show="!search">Todavía no hay productos. Pídele al encargado que los registre.</p>
            </div>
        </div>
    </section>

    {{-- ── Derecha: la venta actual ────────────────────────────────────── --}}
    <aside class="lg:w-80 xl:w-96 2xl:w-104 shrink-0 bg-white border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col max-h-[55%] lg:max-h-none">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between shrink-0">
            <h2 class="font-bold text-slate-800">Venta actual</h2>
            <button type="button" x-show="cart.length" x-cloak
                    @click="if (confirm('¿Quitar todos los productos de esta venta?')) clearCart()"
                    class="text-sm text-slate-500 hover:text-red-600"><i class="fas fa-trash-can"></i> Vaciar</button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-slate-100">
            <template x-for="line in cart" :key="line.key">
                <div class="px-4 py-3">
                    <div class="flex items-start gap-2">
                        <p class="flex-1 text-sm font-semibold text-slate-800 leading-snug">
                            <span x-text="line.name"></span>
                            <span x-show="line.detail" class="ml-1 px-1.5 py-0.5 rounded bg-brand-100 text-brand-700 text-xs font-bold" x-text="line.detail"></span>
                        </p>
                        <button type="button" @click="remove(line)" title="Quitar" class="w-7 h-7 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-3 mt-2">
                        {{-- Unidades: botones − y +; kilos/litros: se escribe el peso --}}
                        <template x-if="!line.fractional">
                            <div class="flex items-center">
                                <button type="button" @click="decrease(line)" class="w-10 h-10 rounded-l-lg bg-slate-100 hover:bg-slate-200 text-lg font-bold">−</button>
                                <span class="w-12 h-10 flex items-center justify-center bg-slate-50 font-bold" x-text="line.qty"></span>
                                <button type="button" @click="increase(line)" class="w-10 h-10 rounded-r-lg bg-slate-100 hover:bg-slate-200 text-lg font-bold">+</button>
                            </div>
                        </template>
                        <template x-if="line.fractional">
                            <label class="flex items-center gap-2">
                                <input type="text" inputmode="decimal" :value="line.qty" @change="setQty(line, $event.target.value)" @focus="$event.target.select()"
                                       class="w-20 h-10 px-2 text-center font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                                <span class="text-sm text-slate-500" x-text="line.unit.toLowerCase()"></span>
                            </label>
                        </template>
                        {{-- Tocar el precio para rebajarlo --}}
                        <button type="button" @click="openPrice(line)" :title="settings.allowDiscounts ? 'Rebajar precio' : ''"
                                :class="line.price < line.listPrice ? 'bg-amber-50 text-amber-700' : 'text-slate-400 hover:bg-slate-100'"
                                class="text-xs px-1.5 py-1 rounded">
                            × <span x-show="line.price < line.listPrice" class="line-through mr-0.5" x-text="money(line.listPrice)"></span><span x-text="money(line.price)"></span>
                            <i class="fas fa-pen text-[10px] ml-0.5" x-show="settings.allowDiscounts"></i>
                        </button>
                        <span class="ml-auto font-bold text-slate-800" x-text="money(lineTotal(line))"></span>
                    </div>
                </div>
            </template>

            <div x-show="!cart.length" class="text-center py-10 px-6 text-slate-400">
                <i class="fas fa-cart-shopping text-4xl mb-3"></i>
                <p>Toca un producto o pasa su código de barras para agregarlo.</p>
            </div>
        </div>

        <div class="p-4 border-t border-slate-200 shrink-0 bg-slate-50">
            {{-- Descuento a toda la venta --}}
            <div class="flex items-center justify-between text-sm mb-2" x-show="cart.length && settings.allowDiscounts">
                <button type="button" @click="openDiscount()" class="text-brand-700 hover:underline font-medium">
                    <i class="fas fa-tag"></i> <span x-text="discount > 0 ? 'Cambiar descuento' : 'Hacer descuento'"></span>
                </button>
                <span x-show="savings > 0" class="text-amber-700" x-text="'Descontaste ' + money(savings)"></span>
            </div>
            <div x-show="discount > 0" class="flex justify-between text-sm text-slate-500 mb-1">
                <span>Subtotal</span><span x-text="money(subtotal)"></span>
            </div>
            <div x-show="discount > 0" class="flex justify-between text-sm text-amber-700 mb-1">
                <span x-text="'Descuento' + (discountMode === 'percent' ? ' ' + discountValue + '%' : '')"></span>
                <span x-text="'− ' + money(discount)"></span>
            </div>
            <p x-show="overLimit" class="text-sm text-red-600 mb-2">
                <i class="fas fa-circle-exclamation"></i> Pasa del máximo permitido (<span x-text="settings.maxDiscountPercent"></span>% = <span x-text="money(maxDiscount)"></span>).
            </p>
            <div class="flex items-end justify-between mb-3">
                <span class="text-slate-500">Total <span class="text-xs" x-show="cart.length" x-text="'(' + itemsCount + ' productos)'"></span></span>
                {{-- En grande lo que se pide en efectivo (redondeado a S/ 0.10); en pequeño el exacto --}}
                <span class="text-4xl font-bold text-slate-800" x-text="money(cashTotal)"></span>
            </div>
            <p x-show="cart.length && cashTotal !== total" x-cloak class="-mt-2 mb-3 text-right text-xs text-slate-400">
                Exacto <span x-text="money(total)"></span> · para Yape, Plin o tarjeta
            </p>
            <button type="button" @click="openCheckout()" :disabled="!cart.length"
                    class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white text-xl font-bold rounded-xl shadow flex items-center justify-center gap-3">
                <i class="fas fa-money-bill-wave"></i> Cobrar
            </button>
        </div>
    </aside>

    {{-- ── Rebajar el precio de un producto ───────────────────────────── --}}
    <div x-show="priceLine" x-cloak x-transition.opacity @keydown.escape.window="priceLine = null; focusSearch()"
         class="fixed inset-0 bg-black/60 z-40 flex items-center justify-center p-3">
        <form @submit.prevent="savePrice()" @click.outside="priceLine = null; focusSearch()" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <template x-if="priceLine">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Rebajar precio</h3>
                    <p class="text-slate-500 mb-4"><span x-text="priceLine.name"></span>
                        <span x-show="priceLine.detail" x-text="'(' + priceLine.detail + ')'"></span> ·
                        precio normal <strong x-text="money(priceLine.listPrice)"></strong></p>

                    <label class="block text-sm font-medium text-slate-700 mb-2">¿A cuánto se lo dejas?</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-slate-400">S/</span>
                        <input type="text" inputmode="decimal" x-ref="priceInput" x-model="priceInput" :placeholder="priceLine.listPrice.toFixed(2)"
                               class="w-full pl-14 pr-4 py-4 text-3xl font-bold border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                    </div>
                    <p class="text-sm mt-2 text-amber-700" x-show="toNumber(priceInput) > 0 && toNumber(priceInput) < priceLine.listPrice"
                       x-text="'Rebaja de ' + money((priceLine.listPrice - toNumber(priceInput)) * priceLine.qty)"></p>

                    <div class="grid grid-cols-2 gap-3 mt-5">
                        <button type="button" @click="priceInput = ''; savePrice()" class="py-3 rounded-xl bg-slate-100 hover:bg-slate-200 font-semibold">Precio normal</button>
                        <button type="submit" class="py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Aplicar</button>
                    </div>
                </div>
            </template>
        </form>
    </div>

    {{-- ── Descuento a toda la venta ───────────────────────────────────── --}}
    <div x-show="discountOpen" x-cloak x-transition.opacity @keydown.escape.window="discountOpen = false; focusSearch()"
         class="fixed inset-0 bg-black/60 z-40 flex items-center justify-center p-3">
        <form @submit.prevent="saveDiscount()" @click.outside="discountOpen = false; focusSearch()" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-slate-800">Descuento a la venta</h3>
            <p class="text-slate-500 mb-4">Venta de <strong x-text="money(subtotal)"></strong>. Máximo <span x-text="settings.maxDiscountPercent"></span>% en total.</p>

            <div class="grid grid-cols-2 gap-2 mb-3">
                <button type="button" @click="discountMode = 'amount'" :class="discountMode === 'amount' ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200'"
                        class="py-2.5 rounded-lg border-2 font-semibold">En soles (S/)</button>
                <button type="button" @click="discountMode = 'percent'" :class="discountMode === 'percent' ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200'"
                        class="py-2.5 rounded-lg border-2 font-semibold">En porcentaje (%)</button>
            </div>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-slate-400" x-text="discountMode === 'amount' ? 'S/' : '%'"></span>
                <input type="text" inputmode="decimal" x-ref="discountInput" x-model="discountDraft" placeholder="0"
                       class="w-full pl-14 pr-4 py-4 text-3xl font-bold border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
            </div>
            <div class="flex gap-2 mt-3" x-show="discountMode === 'percent'">
                <template x-for="p in [5, 10, 15]" :key="p">
                    <button type="button" x-show="p <= settings.maxDiscountPercent" @click="discountDraft = String(p)" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 font-semibold" x-text="p + '%'"></button>
                </template>
            </div>
            <p class="text-sm mt-3 text-slate-600" x-show="toNumber(discountDraft) > 0"
               x-text="'Queda en ' + money(subtotal - (discountMode === 'percent' ? subtotal * toNumber(discountDraft) / 100 : toNumber(discountDraft)))"></p>

            <div class="grid grid-cols-2 gap-3 mt-5">
                <button type="button" @click="discountDraft = ''; saveDiscount()" class="py-3 rounded-xl bg-slate-100 hover:bg-slate-200 font-semibold">Sin descuento</button>
                <button type="submit" class="py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">Aplicar</button>
            </div>
        </form>
    </div>

    {{-- ── ¿Cómo lo vende? (unidad, media caja, caja) ──────────────────── --}}
    <div x-show="chooser" x-cloak x-transition.opacity @keydown.escape.window="chooser = null; focusSearch()"
         class="fixed inset-0 bg-black/60 z-40 flex items-center justify-center p-3">
        <div @click.outside="chooser = null; focusSearch()" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
            <template x-if="chooser">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-xl font-bold text-slate-800" x-text="chooser.name"></h3>
                        <button type="button" @click="chooser = null; focusSearch()" class="w-9 h-9 rounded-full text-slate-400 hover:bg-slate-100"><i class="fas fa-xmark"></i></button>
                    </div>
                    <p class="text-slate-500 mb-5">¿Cómo lo lleva? <span class="text-xs" x-text="'(quedan ' + qtyLabel(chooser.stock) + ')'"></span></p>
                    <div class="grid gap-3">
                        <button type="button" @click="choose(null)"
                                class="flex items-center justify-between px-5 py-4 rounded-xl border-2 border-slate-200 hover:border-brand-500 hover:bg-brand-50">
                            <span class="text-lg font-bold">Suelto <span class="text-sm font-normal text-slate-500">(1 unidad)</span></span>
                            <span class="text-xl font-bold text-brand-700" x-text="money(chooser.price)"></span>
                        </button>
                        <template x-for="p in chooser.presentations" :key="p.id">
                            <button type="button" @click="choose(p)" :disabled="p.units > chooser.stock"
                                    class="flex items-center justify-between px-5 py-4 rounded-xl border-2 border-slate-200 hover:border-brand-500 hover:bg-brand-50 disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:bg-white">
                                <span class="text-lg font-bold text-left">
                                    <span x-text="p.name"></span>
                                    <span class="text-sm font-normal text-slate-500" x-text="'(' + qtyLabel(p.units) + ' unidades)'"></span>
                                    <span class="block text-xs font-normal text-red-500" x-show="p.units > chooser.stock">No alcanza el stock</span>
                                </span>
                                <span class="text-xl font-bold text-brand-700" x-text="money(p.price)"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── ¿Cuánto pesa? (kilo / litro) ────────────────────────────────── --}}
    <div x-show="weigh" x-cloak x-transition.opacity @keydown.escape.window="weigh = null; focusSearch()"
         class="fixed inset-0 bg-black/60 z-40 flex items-center justify-center p-3">
        <form @submit.prevent="confirmWeigh()" @click.outside="weigh = null; focusSearch()" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
            <template x-if="weigh">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-xl font-bold text-slate-800" x-text="weigh.name"></h3>
                        <button type="button" @click="weigh = null; focusSearch()" class="w-9 h-9 rounded-full text-slate-400 hover:bg-slate-100"><i class="fas fa-xmark"></i></button>
                    </div>
                    <p class="text-slate-500 mb-5"><span x-text="money(weigh.price)"></span> el <span x-text="weigh.unit.toLowerCase()"></span></p>

                    <label class="block text-sm font-medium text-slate-700 mb-2"><i class="fas fa-scale-balanced"></i> ¿Cuánto marcó la balanza?</label>
                    <div class="relative">
                        <input type="text" inputmode="decimal" x-ref="weighQty" x-model="weighQty" @input="weighAmount = ''" placeholder="0.000"
                               class="w-full pl-4 pr-20 py-4 text-3xl font-bold border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-lg text-slate-400" x-text="weigh.unit === 'Kilo' ? 'kg' : 'L'"></span>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mt-3">
                        <template x-for="quick in [{ label: '¼', value: 0.25 }, { label: '½', value: 0.5 }, { label: '¾', value: 0.75 }, { label: '1', value: 1 }]" :key="quick.value">
                            <button type="button" @click="weighQty = String(quick.value); weighAmount = ''"
                                    class="py-3 rounded-lg bg-slate-100 hover:bg-slate-200 font-bold text-lg" x-text="quick.label + (weigh.unit === 'Kilo' ? ' kg' : ' L')"></button>
                        </template>
                    </div>

                    <div class="flex items-center gap-3 my-4 text-sm text-slate-400"><span class="flex-1 border-t"></span>o el cliente pide por soles<span class="flex-1 border-t"></span></div>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">Por S/</span>
                        <input type="text" inputmode="decimal" x-model="weighAmount" @input="weighByAmount()" placeholder="2.00"
                               class="w-full pl-20 pr-4 py-3 text-xl font-bold border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                    </div>

                    <button type="submit" :disabled="!(Number(weighQty) > 0)"
                            class="mt-5 w-full py-4 bg-brand-600 hover:bg-brand-700 disabled:bg-slate-300 text-white text-lg font-bold rounded-xl">
                        Agregar <span x-show="Number(weighQty) > 0" x-text="qtyLabel(weighQty) + (weigh.unit === 'Kilo' ? ' kg' : ' L') + ' = ' + money(weighTotal)"></span>
                    </button>
                </div>
            </template>
        </form>
    </div>

    {{-- ── Cobro: paso a paso ─────────────────────────────────────────── --}}
    <div x-show="checkout" x-cloak x-transition.opacity @keydown.escape.window="step !== 'done' && closeCheckout()"
         class="fixed inset-0 bg-black/60 z-40 flex items-center justify-center p-3">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-full overflow-y-auto">

            {{-- Paso 1: ¿cómo paga? --}}
            <div x-show="step === 'method'" class="p-6">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-xl font-bold text-slate-800">¿Cómo paga el cliente?</h3>
                    <button type="button" @click="closeCheckout()" class="w-9 h-9 rounded-full text-slate-400 hover:bg-slate-100"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="mb-5">
                    <p class="text-slate-500">Total a cobrar: <strong class="text-slate-800 text-2xl" x-text="money(cashTotal)"></strong></p>
                    <p x-show="cashTotal !== total" class="text-xs text-slate-400">Exacto <span x-text="money(total)"></span> · con Yape, Plin o tarjeta se cobra el exacto</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    @foreach ([
                        'efectivo' => ['fa-money-bill-wave', 'Efectivo'],
                        'yape'     => ['fa-mobile-screen', 'Yape'],
                        'plin'     => ['fa-mobile-screen-button', 'Plin'],
                        'tarjeta'  => ['fa-credit-card', 'Tarjeta'],
                        'fiado'    => ['fa-handshake', 'Fiado'],
                    ] as $key => [$icon, $label])
                        <button type="button" @click="chooseMethod('{{ $key }}')"
                                class="{{ $key === 'efectivo' ? 'col-span-2 bg-emerald-600 text-white hover:bg-emerald-700 border-emerald-600' : 'bg-white hover:border-brand-500 hover:bg-brand-50 border-slate-200' }} border-2 rounded-xl py-5 flex flex-col items-center gap-2 font-bold text-lg">
                            <i class="fas {{ $icon }} text-2xl"></i>{{ $label }}
                            @if(in_array($key, \App\Models\Sale::ROUNDED_METHODS))
                                <span class="text-2xl" x-text="money(cashTotal)"></span>
                            @else
                                <span class="text-sm font-medium text-slate-500" x-text="money(total)"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Paso 2: detalles según la forma de pago + comprobante --}}
            <div x-show="step === 'details'" class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <button type="button" @click="step = 'method'; error = ''" class="w-9 h-9 rounded-full text-slate-500 hover:bg-slate-100" title="Volver">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h3 class="text-xl font-bold text-slate-800 flex-1">
                        Pago con <span x-text="methods[method]"></span>
                    </h3>
                    <span class="text-xl font-bold text-slate-800" x-text="money(charged)"></span>
                </div>

                {{-- Efectivo: con cuánto paga y el vuelto --}}
                <template x-if="method === 'efectivo'">
                    <div>
                        {{-- Redondeo a S/ 0.10: se explica para que el cajero no se confunda --}}
                        <p x-show="charged !== total" class="mb-3 px-3 py-2 rounded-lg bg-sky-50 text-sky-800 text-sm">
                            <i class="fas fa-coins"></i>
                            La venta es <strong x-text="money(total)"></strong>. En efectivo se cobra
                            <strong x-text="money(charged)"></strong> (redondeo a 10 céntimos).
                        </p>
                        <label class="block text-sm font-medium text-slate-700 mb-2">¿Con cuánto paga?</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-slate-400">S/</span>
                            <input type="text" inputmode="decimal" x-ref="received" x-model="received" @keydown.enter.prevent="confirm()"
                                   :placeholder="charged.toFixed(2)"
                                   class="w-full pl-14 pr-4 py-4 text-3xl font-bold border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                        </div>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <button type="button" @click="received = charged.toFixed(2)" class="px-4 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 font-semibold">Exacto</button>
                            <template x-for="bill in bills" :key="bill">
                                <button type="button" @click="received = String(bill)" class="px-4 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 font-semibold" x-text="'S/ ' + bill"></button>
                            </template>
                        </div>
                        <div class="mt-4 p-4 rounded-xl text-center"
                             :class="missing > 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800'">
                            <template x-if="missing > 0">
                                <p class="text-lg">Faltan <strong class="text-2xl" x-text="money(missing)"></strong></p>
                            </template>
                            <template x-if="missing === 0">
                                <p class="text-lg">Vuelto: <strong class="text-3xl" x-text="money(change ?? 0)"></strong></p>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Yape, Plin, tarjeta: solo confirmar que llegó --}}
                <template x-if="['yape', 'plin', 'tarjeta'].includes(method)">
                    <p class="p-4 rounded-xl bg-sky-50 text-sky-800">
                        <i class="fas fa-circle-info"></i>
                        Confirma que el pago de <strong x-text="money(total)"></strong> llegó antes de continuar.
                    </p>
                </template>

                {{-- Fiado: elegir o crear al cliente --}}
                <template x-if="method === 'fiado'">
                    <div>
                        <template x-if="customer.id || newClient">
                            <div class="p-4 rounded-xl border-2 border-brand-500 bg-brand-50">
                                <template x-if="customer.id">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-user text-brand-700 text-xl"></i>
                                        <div class="flex-1">
                                            <p class="font-bold text-slate-800" x-text="customer.name"></p>
                                            <p class="text-sm text-slate-500" x-show="customer.debt > 0">Ya debe <strong x-text="money(customer.debt)"></strong></p>
                                        </div>
                                        <button type="button" @click="resetCustomer()" class="text-sm text-slate-500 hover:text-slate-800">Cambiar</button>
                                    </div>
                                </template>
                                <template x-if="!customer.id">
                                    <div class="space-y-3">
                                        <p class="font-semibold text-slate-800">Cliente nuevo</p>
                                        <input type="text" x-model="customer.name" placeholder="Nombre (ej. Doña Rosa del 2do piso)"
                                               class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                                        <input type="text" inputmode="tel" x-model="customer.phone" placeholder="Celular (opcional)"
                                               class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                                        <button type="button" @click="resetCustomer()" class="text-sm text-slate-500 hover:text-slate-800">Buscar un cliente que ya existe</button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!customer.id && !newClient">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">¿A quién le fías?</label>
                                <input type="text" x-ref="clientQuery" x-model="clientQuery" @input.debounce.300ms="searchClients()"
                                       placeholder="Escribe su nombre, DNI o celular"
                                       class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-xl focus:outline-none focus:border-brand-500">
                                <div class="mt-2 divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden" x-show="clientResults.length">
                                    <template x-for="client in clientResults" :key="client.id">
                                        <button type="button" @click="pickClient(client)" class="w-full px-4 py-3 text-left hover:bg-brand-50 flex items-center gap-3">
                                            <span class="flex-1 font-medium" x-text="client.name"></span>
                                            <span class="text-sm text-amber-700" x-show="client.debt > 0" x-text="'Debe ' + money(client.debt)"></span>
                                        </button>
                                    </template>
                                </div>
                                <button type="button" @click="newClient = true; customer.name = clientQuery"
                                        class="mt-3 w-full py-3 border-2 border-dashed border-slate-300 rounded-xl text-slate-600 hover:border-brand-500 hover:text-brand-700 font-medium">
                                    <i class="fas fa-user-plus"></i> Es un cliente nuevo
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Comprobante (solo si la tienda emite boleta/factura) --}}
                <template x-if="vouchers.length > 1">
                    <div class="mt-5 pt-5 border-t border-slate-200">
                        <p class="text-sm font-medium text-slate-700 mb-2">¿Qué comprobante quiere?</p>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="type in vouchers" :key="type">
                                <button type="button" @click="voucher = type"
                                        :class="voucher === type ? 'bg-brand-600 text-white border-brand-600' : 'bg-white border-slate-200 hover:border-brand-500'"
                                        class="py-3 rounded-lg border-2 font-semibold capitalize" x-text="type"></button>
                            </template>
                        </div>
                        <div x-show="voucher !== 'ticket'" class="mt-3 space-y-2">
                            <input type="text" inputmode="numeric" x-model="customer.document"
                                   :placeholder="voucher === 'factura' ? 'RUC (11 números) — obligatorio' : 'DNI (opcional)'"
                                   class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                            <input type="text" x-model="customer.name"
                                   :placeholder="voucher === 'factura' ? 'Razón social — obligatorio' : 'Nombre (opcional)'"
                                   class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                            <input type="text" x-show="voucher === 'factura'" x-model="customer.address" placeholder="Dirección (opcional)"
                                   class="w-full px-3 py-3 border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                        </div>
                    </div>
                </template>

                <div x-show="error" x-cloak class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex gap-2">
                    <i class="fas fa-circle-exclamation mt-0.5"></i><span x-text="error"></span>
                </div>

                <button type="button" @click="confirm()" :disabled="!canConfirm || sending"
                        class="mt-5 w-full py-4 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white text-xl font-bold rounded-xl flex items-center justify-center gap-3">
                    <i class="fas" :class="sending ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="sending ? 'Guardando...' : (method === 'fiado' ? 'Anotar fiado de ' : 'Cobrar ') + money(charged)"></span>
                </button>
            </div>

            {{-- Paso 3: listo --}}
            <div x-show="step === 'done'" class="p-8 text-center">
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-emerald-600 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-slate-800">¡Venta registrada!</h3>
                <p class="text-slate-500 mt-1" x-text="result?.number"></p>
                <p class="text-3xl font-bold text-slate-800 mt-3" x-text="money(result?.charged)"></p>
                <p x-show="result && result.charged !== result.total" class="text-xs text-slate-400" x-text="'Exacto ' + money(result?.total) + ' (redondeado a 10 céntimos)'"></p>

                <template x-if="result?.change > 0">
                    <div class="mt-5 p-4 rounded-xl bg-emerald-50">
                        <p class="text-emerald-800">Dale de vuelto</p>
                        <p class="text-5xl font-bold text-emerald-700" x-text="money(result.change)"></p>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-3 mt-6">
                    <button type="button" @click="printTicket()" class="py-4 rounded-xl border-2 border-slate-200 hover:border-brand-500 font-semibold">
                        <i class="fas fa-print"></i> Imprimir ticket
                    </button>
                    <button type="button" @click="newSale()" x-init="$watch('step', s => s === 'done' && $nextTick(() => $el.focus()))"
                            class="py-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold">
                        <i class="fas fa-plus"></i> Nueva venta
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
