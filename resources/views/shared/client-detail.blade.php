{{-- Detalle de un cliente de fiado. Variables: $client, $debt, $pending, $payments, $payUrl, $saleUrl (closure o null) --}}
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Lo que debe</h2>
            </div>
            @forelse($pending as $sale)
                <div class="px-5 py-3 border-b border-slate-100 last:border-0">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium text-slate-800">{{ $sale->created_at->translatedFormat('d \d\e F, H:i') }}</p>
                            <p class="text-xs text-slate-500">{{ $sale->items->map(fn ($i) => qty($i->quantity) . ' ' . $i->product_name)->join(', ') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold text-amber-700">{{ money($sale->credit_balance) }}</p>
                            @if((float) $sale->credit_balance < (float) $sale->total)
                                <p class="text-xs text-slate-400">de {{ money($sale->total) }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-emerald-600"><i class="fas fa-circle-check"></i> No debe nada</p>
            @endforelse
        </div>

        @if($payments->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100">
                    <h2 class="font-semibold text-slate-800">Pagos que hizo</h2>
                </div>
                @foreach($payments as $payment)
                    <div class="px-5 py-2.5 border-b border-slate-100 last:border-0 flex justify-between text-sm">
                        <span class="text-slate-600">{{ $payment->created_at->format('d/m/Y H:i') }} · {{ \App\Models\Sale::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}
                            @if($payment->employee) · cobró {{ $payment->employee->name }} @endif</span>
                        <span class="font-semibold text-emerald-600">{{ money($payment->amount) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 text-center">
            <p class="text-sm text-slate-500">Debe en total</p>
            <p class="text-4xl font-bold {{ $debt > 0 ? 'text-amber-700' : 'text-emerald-600' }}">{{ money($debt) }}</p>
            @if($client->phone)
                <p class="text-sm text-slate-500 mt-2"><i class="fas fa-phone"></i> {{ $client->phone }}</p>
            @endif
        </div>

        @if($debt > 0)
            <form method="POST" action="{{ $payUrl }}" x-data="{ method: '{{ old('payment_method', 'efectivo') }}', sending: false }" @submit="sending = true"
                  class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mt-4 space-y-4">
                @csrf
                <h3 class="font-semibold text-slate-800">Registrar un pago</h3>
                <x-field label="¿Cuánto paga?" name="amount">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">S/</span>
                        <input type="text" inputmode="decimal" name="amount" id="amount" value="{{ old('amount') }}"
                               class="w-full pl-10 pr-3 py-3 text-xl font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-brand-500">
                    </div>
                    <button type="button" onclick="document.getElementById('amount').value = '{{ number_format($debt, 2, '.', '') }}'"
                            class="mt-2 text-sm text-brand-700 hover:underline">Paga todo ({{ money($debt) }})</button>
                </x-field>
                <div>
                    <p class="text-sm font-medium text-slate-700 mb-1.5">¿Cómo paga?</p>
                    <input type="hidden" name="payment_method" :value="method">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(array_diff_key(\App\Models\Sale::PAYMENT_METHODS, ['fiado' => true]) as $key => $label)
                            <button type="button" @click="method = '{{ $key }}'"
                                    :class="method === '{{ $key }}' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white border-slate-200'"
                                    class="py-2.5 rounded-lg border-2 font-medium text-sm">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <button type="submit" :disabled="sending" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold rounded-xl">
                    Registrar pago
                </button>
            </form>
        @endif
    </div>
</div>
