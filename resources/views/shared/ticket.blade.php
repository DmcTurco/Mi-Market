{{-- Ticket para impresora térmica de 80 mm. Se imprime solo al abrirse. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->voucherNumber() }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { width: 72mm; margin: 0 auto; padding: 4mm 0; font: 12px/1.35 'Courier New', monospace; color: #000; }
        .c { text-align: center; }
        .r { text-align: right; }
        .b { font-weight: bold; }
        .big { font-size: 16px; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .void { border: 2px solid #000; padding: 4px; margin: 6px 0; font-size: 16px; }
        .no-print { margin-top: 12px; display: flex; gap: 8px; }
        .no-print button { flex: 1; padding: 10px; font-size: 14px; cursor: pointer; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="c">
        <div class="b big">{{ $sale->company->name }}</div>
        @if($sale->company->ruc)<div>RUC {{ $sale->company->ruc }}</div>@endif
        @if($sale->company->address)<div>{{ $sale->company->address }}</div>@endif
        @if($sale->company->phone)<div>Tel. {{ $sale->company->phone }}</div>@endif
    </div>
    <hr>
    <div class="c b">
        {{ $sale->voucher_type === 'ticket' ? 'NOTA DE VENTA' : mb_strtoupper($sale->voucherLabel()) . ' DE VENTA ELECTRÓNICA' }}<br>
        {{ $sale->voucherNumber() }}
    </div>
    <div>Fecha: {{ $sale->created_at->format('d/m/Y H:i') }}</div>
    <div>Atendió: {{ $sale->employee->name }}</div>
    @if($sale->customer_name)
        <div>Cliente: {{ $sale->customer_name }}</div>
        @if($sale->customer_document)<div>{{ strtoupper($sale->customer_document_type ?? '') }}: {{ $sale->customer_document }}</div>@endif
        @if($sale->customer_address)<div>Dir.: {{ $sale->customer_address }}</div>@endif
    @endif

    @if($sale->isVoided())
        <div class="void c b">ANULADA</div>
    @endif
    <hr>

    <table>
        @foreach($sale->items as $item)
            <tr><td colspan="2">{{ $item->product_name }}</td></tr>
            <tr>
                <td>&nbsp;&nbsp;{{ qty($item->quantity) }} x {{ number_format($item->list_price ?? $item->unit_price, 2) }}</td>
                <td class="r">{{ number_format((float) $item->total + (float) $item->discount, 2) }}</td>
            </tr>
            @if((float) $item->discount > 0)
                <tr><td>&nbsp;&nbsp;Descuento</td><td class="r">-{{ number_format($item->discount, 2) }}</td></tr>
            @endif
        @endforeach
    </table>
    <hr>

    <table>
        @if((float) $sale->discount_amount > 0)
            <tr><td>Ahorraste</td><td class="r">-{{ number_format($sale->discount_amount, 2) }}</td></tr>
        @endif
        @if($sale->voucher_type !== 'ticket')
            @if((float) $sale->taxable_amount > 0)<tr><td>Op. gravada</td><td class="r">{{ number_format($sale->taxable_amount, 2) }}</td></tr>@endif
            @if((float) $sale->exonerated_amount > 0)<tr><td>Op. exonerada</td><td class="r">{{ number_format($sale->exonerated_amount, 2) }}</td></tr>@endif
            @if((float) $sale->unaffected_amount > 0)<tr><td>Op. inafecta</td><td class="r">{{ number_format($sale->unaffected_amount, 2) }}</td></tr>@endif
            <tr><td>IGV 18%</td><td class="r">{{ number_format($sale->igv, 2) }}</td></tr>
        @endif
        <tr class="b big"><td>TOTAL</td><td class="r">{{ money($sale->total) }}</td></tr>
        @if((float) $sale->rounding_amount != 0)
            <tr><td>Redondeo</td><td class="r">{{ (float) $sale->rounding_amount > 0 ? '-' : '+' }}{{ number_format(abs((float) $sale->rounding_amount), 2) }}</td></tr>
            <tr class="b"><td>COBRADO</td><td class="r">{{ money($sale->charged()) }}</td></tr>
        @endif
        <tr><td>Pago</td><td class="r">{{ $sale->paymentLabel() }}</td></tr>
        @if($sale->amount_received !== null)
            <tr><td>Recibido</td><td class="r">{{ number_format($sale->amount_received, 2) }}</td></tr>
            <tr><td>Vuelto</td><td class="r">{{ number_format($sale->change, 2) }}</td></tr>
        @endif
    </table>
    <hr>
    <div class="c">¡Gracias por su compra!</div>

    <div class="no-print">
        <button onclick="window.print()">Imprimir otra vez</button>
        <button onclick="window.close()">Cerrar</button>
    </div>
</body>
</html>
