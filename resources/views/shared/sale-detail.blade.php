{{-- Detalle de una venta. Variables: $sale, $ticketUrl, $voidUrl (null = no se puede anular aquí) --}}
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Producto</th>
                    <th class="text-right px-4 py-3">Cantidad</th>
                    <th class="text-right px-4 py-3">Precio</th>
                    <th class="text-right px-5 py-3">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($sale->items as $item)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-800">
                            {{ $item->product_name }}
                            @if((float) $item->discount > 0)
                                <span class="block text-xs font-normal text-amber-700">Descuento {{ money($item->discount) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ qty($item->quantity) }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">
                            @if($item->list_price !== null && (float) $item->unit_price < (float) $item->list_price)
                                <span class="line-through text-xs">{{ money($item->list_price) }}</span>
                            @endif
                            {{ money($item->unit_price) }}
                        </td>
                        <td class="px-5 py-3 text-right font-semibold">{{ money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-slate-200">
                @if((float) $sale->discount_amount > 0)
                    <tr>
                        <td colspan="3" class="px-5 pt-3 text-right text-slate-500">A precio normal</td>
                        <td class="px-5 pt-3 text-right text-slate-500">{{ money($sale->gross_total) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-5 text-right text-amber-700">Descuentos</td>
                        <td class="px-5 text-right text-amber-700">− {{ money($sale->discount_amount) }}</td>
                    </tr>
                @endif
                @if((float) $sale->rounding_amount != 0)
                    <tr>
                        <td colspan="3" class="px-5 text-right text-slate-500">Redondeo del efectivo · se cobró {{ money($sale->charged()) }}</td>
                        <td class="px-5 text-right text-slate-500">{{ (float) $sale->rounding_amount > 0 ? '−' : '+' }} {{ money(abs((float) $sale->rounding_amount)) }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="3" class="px-5 py-3 text-right font-semibold text-slate-600">Total</td>
                    <td class="px-5 py-3 text-right text-xl font-bold">{{ money($sale->total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-2 text-sm">
            @if($sale->isVoided())
                <div class="p-3 rounded-lg bg-red-50 text-red-700 mb-3">
                    <p class="font-bold"><i class="fas fa-ban"></i> Venta anulada</p>
                    <p class="text-xs mt-1">{{ $sale->voided_at?->format('d/m/Y H:i') }} · {{ $sale->void_reason }}</p>
                </div>
            @endif
            <p class="flex justify-between"><span class="text-slate-500">Fecha</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></p>
            <p class="flex justify-between"><span class="text-slate-500">Atendió</span><span>{{ $sale->employee?->name }}</span></p>
            <p class="flex justify-between"><span class="text-slate-500">Pago</span><span class="font-semibold">{{ $sale->paymentLabel() }}</span></p>
            @if($sale->amount_received !== null)
                <p class="flex justify-between"><span class="text-slate-500">Recibido / vuelto</span><span>{{ money($sale->amount_received) }} / {{ money($sale->change) }}</span></p>
            @endif
            @if($sale->customer_name)
                <p class="flex justify-between"><span class="text-slate-500">Cliente</span><span>{{ $sale->customer_name }}</span></p>
            @endif
            @if($sale->payment_method === 'fiado' && !$sale->isVoided())
                <p class="flex justify-between"><span class="text-slate-500">Falta pagar</span>
                    <span class="font-semibold {{ (float) $sale->credit_balance > 0 ? 'text-amber-700' : 'text-emerald-600' }}">{{ (float) $sale->credit_balance > 0 ? money($sale->credit_balance) : 'Pagado' }}</span></p>
            @endif
        </div>

        <a href="{{ $ticketUrl }}" target="_blank"
           class="flex items-center justify-center gap-2 w-full py-3 bg-white border-2 border-slate-200 hover:border-brand-500 rounded-xl font-semibold text-slate-700">
            <i class="fas fa-print"></i> Imprimir ticket
        </a>

        @if($voidUrl && !$sale->isVoided())
            <form method="POST" action="{{ $voidUrl }}" x-data="{ open: {{ $errors->has('reason') ? 'true' : 'false' }} }"
                  class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                @csrf
                <button type="button" x-show="!open" @click="open = true; $nextTick(() => $refs.reason.focus())"
                        class="w-full py-3 rounded-xl text-red-600 hover:bg-red-50 font-semibold">
                    <i class="fas fa-ban"></i> Anular esta venta
                </button>
                <div x-show="open" x-cloak class="space-y-3">
                    <p class="text-sm text-slate-600">Los productos vuelven al stock y el dinero sale de la caja.</p>
                    <x-field label="¿Por qué se anula?" name="reason">
                        <x-input name="reason" x-ref="reason" placeholder="Ej. el cliente se arrepintió" />
                    </x-field>
                    <div class="flex gap-2">
                        <button type="button" @click="open = false" class="flex-1 py-2.5 rounded-lg bg-slate-100 font-medium">No</button>
                        <button type="submit" class="flex-1 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold">Sí, anular</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
