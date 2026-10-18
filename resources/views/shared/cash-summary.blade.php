{{-- Resumen de una caja. Variables: $register, $summary (CashRegister::summary()) --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100">
        <h2 class="font-semibold text-slate-800">Ventas del turno <span class="text-slate-400 font-normal">({{ $summary['sales_count'] }})</span></h2>
    </div>
    <dl class="divide-y divide-slate-100 text-sm">
        @foreach(\App\Models\Sale::PAYMENT_METHODS as $key => $label)
            @if($summary['by_method'][$key] > 0 || $key === 'efectivo')
                <div class="px-5 py-2.5 flex justify-between">
                    <dt class="text-slate-500">{{ $label }}</dt>
                    <dd class="font-medium">{{ money($summary['by_method'][$key]) }}</dd>
                </div>
            @endif
        @endforeach
        <div class="px-5 py-2.5 flex justify-between bg-slate-50 font-semibold">
            <dt>Total vendido</dt><dd>{{ money($summary['sales_total']) }}</dd>
        </div>
    </dl>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mt-4">
    <div class="px-5 py-3 border-b border-slate-100">
        <h2 class="font-semibold text-slate-800">Efectivo en el cajón</h2>
    </div>
    <dl class="divide-y divide-slate-100 text-sm">
        <div class="px-5 py-2.5 flex justify-between"><dt class="text-slate-500">Empezaste con</dt><dd>{{ money($register->opening_amount) }}</dd></div>
        <div class="px-5 py-2.5 flex justify-between"><dt class="text-slate-500">+ Ventas en efectivo</dt><dd>{{ money($summary['by_method']['efectivo']) }}</dd></div>
        @if($summary['credit_payments_cash'] > 0)
            <div class="px-5 py-2.5 flex justify-between"><dt class="text-slate-500">+ Pagos de fiados en efectivo</dt><dd>{{ money($summary['credit_payments_cash']) }}</dd></div>
        @endif
        @if($summary['income'] > 0)
            <div class="px-5 py-2.5 flex justify-between"><dt class="text-slate-500">+ Otros ingresos</dt><dd>{{ money($summary['income']) }}</dd></div>
        @endif
        @if($summary['expense'] > 0)
            <div class="px-5 py-2.5 flex justify-between"><dt class="text-slate-500">− Gastos</dt><dd class="text-red-600">{{ money($summary['expense']) }}</dd></div>
        @endif
        <div class="px-5 py-3 flex justify-between bg-emerald-50 text-emerald-800 font-bold text-base">
            <dt>Debe haber</dt><dd>{{ money($summary['expected_cash']) }}</dd>
        </div>
    </dl>
</div>
