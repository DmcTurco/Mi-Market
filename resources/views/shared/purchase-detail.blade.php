{{-- Detalle de un ingreso de mercadería. Variable: $purchase --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden max-w-3xl">
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
