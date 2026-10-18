{{-- Detalle de un ingreso de mercadería. Variables: $purchase, $voidUrl (null = no puede anularlo) --}}
@if($purchase->isVoided())
    <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700">
        <p class="font-bold"><i class="fas fa-ban"></i> Ingreso anulado</p>
        <p class="text-sm mt-1">{{ $purchase->voided_at?->format('d/m/Y H:i') }} · {{ $purchase->void_reason }}. El stock de estos productos ya se devolvió.</p>
    </div>
@endif

<div @class(['bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden', 'opacity-60' => $purchase->isVoided()])>
    <div class="px-5 py-3 border-b border-slate-100 text-sm text-slate-600 flex flex-wrap gap-x-6 gap-y-1">
        <span><i class="fas fa-calendar"></i> {{ $purchase->created_at->format('d/m/Y H:i') }}</span>
        <span><i class="fas fa-user"></i> {{ $purchase->employee?->name ?? 'La empresa' }}</span>
        @if($purchase->supplier)<span><i class="fas fa-truck"></i> {{ $purchase->supplier }}</span>@endif
        @if($purchase->cash_movement_id)<span class="text-amber-700"><i class="fas fa-cash-register"></i> Pagado con dinero de la caja</span>@endif
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                <th class="text-left px-5 py-3">Producto</th>
                <th class="text-right px-4 py-3">Llegaron</th>
                <th class="text-right px-4 py-3">Costo c/u</th>
                <th class="text-right px-5 py-3">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($purchase->items as $item)
                <tr>
                    <td class="px-5 py-3 font-medium">{{ $item->product->name }}</td>
                    <td class="px-4 py-3 text-right">{{ qty($item->quantity) }}</td>
                    <td class="px-4 py-3 text-right text-slate-500">{{ money($item->unit_cost) }}</td>
                    <td class="px-5 py-3 text-right font-semibold">{{ money($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t-2 border-slate-200">
            <tr>
                <td colspan="3" class="px-5 py-3 text-right font-semibold">Total</td>
                <td class="px-5 py-3 text-right text-lg font-bold">{{ money($purchase->total) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- ¿Te equivocaste? Se anula y se vuelve a registrar bien --}}
@if(($voidUrl ?? null) && !$purchase->isVoided())
    <form method="POST" action="{{ $voidUrl }}" x-data="{ open: {{ $errors->has('reason') ? 'true' : 'false' }}, sending: false }" @submit="sending = true"
          class="mt-4 bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        @csrf
        <button type="button" x-show="!open" @click="open = true; $nextTick(() => $refs.reason.focus())"
                class="px-4 py-2.5 rounded-lg text-red-600 hover:bg-red-50 font-semibold">
            <i class="fas fa-ban"></i> ¿Te equivocaste? Anular este ingreso
        </button>
        <div x-show="open" x-cloak class="space-y-3">
            <p class="text-sm text-slate-600">
                El stock baja lo que había subido{{ $purchase->cash_movement_id ? ' y, si la caja sigue abierta, el dinero vuelve a la caja' : '' }}.
                Después puedes registrarlo de nuevo con los datos correctos.
            </p>
            <x-field label="¿Qué estuvo mal?" name="reason">
                <x-input name="reason" x-ref="reason" placeholder="Ej. puse el precio de una botella en vez del de la caja" />
            </x-field>
            <div class="flex gap-2">
                <button type="button" @click="open = false" class="px-5 py-2.5 rounded-lg bg-slate-100 font-medium">No</button>
                <button type="submit" :disabled="sending" class="px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-semibold">Sí, anular</button>
            </div>
        </div>
    </form>
@endif
