<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\AuditLog;
use App\Models\CashRegister;
use App\Models\Client;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Support\Qty;
use Illuminate\Support\Facades\DB;

/**
 * Registra y anula ventas del punto de venta.
 * Los precios se toman siempre de la base de datos (el navegador solo manda producto y cantidad).
 */
class SaleService
{
    /** SUNAT: una boleta desde este monto debe identificar al cliente (DNI) */
    const BOLETA_ID_LIMIT = 700;

    public function __construct(
        private TaxCalculator $tax,
        private StockService $stock,
    ) {}

    /**
     * @param  array{items: array<int, array{product_id: int, presentation_id?: ?int, quantity: float}>, voucher_type: string,
     *               payment_method: string, amount_received?: ?float, client_id?: ?int,
     *               customer_name?: ?string, customer_document?: ?string, customer_address?: ?string,
     *               customer_phone?: ?string}  $data
     *
     * @throws BusinessException
     */
    public function create(Employee $employee, CashRegister $register, array $data): Sale
    {
        if (!$register->isOpen()) {
            throw new BusinessException('Tu caja está cerrada. Ábrela para poder vender.');
        }

        $company   = $employee->company;
        $companyId = (int) $company->id;
        $items     = $this->mergeItems($data['items']);
        $voucher   = $data['voucher_type'];
        $method    = $data['payment_method'];

        return DB::transaction(function () use ($employee, $register, $data, $company, $companyId, $items, $voucher, $method) {
            // Se bloquean en orden de id para que dos cajas vendiendo lo mismo no se traben
            $products = Product::where('company_id', $companyId)
                ->whereIn('id', array_unique(array_column($items, 'product_id')))
                ->with('presentations')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                if (!$product || !$product->active) {
                    throw new BusinessException('Uno de los productos ya no está disponible. Quítalo de la venta y vuelve a buscarlo.');
                }

                $presentation = null;
                if ($item['presentation_id']) {
                    $presentation = $product->presentations->firstWhere('id', $item['presentation_id']);
                    if (!$presentation) {
                        throw new BusinessException("La presentación de «{$product->name}» cambió. Quítalo de la venta y vuelve a agregarlo.");
                    }
                }

                // Por kilo o litro se vende cualquier cantidad; unidades, cajas y paquetes van enteros
                $wholeOnly = $presentation !== null || !$product->isFractional();
                if ($wholeOnly && floor($item['quantity']) != $item['quantity']) {
                    $how = $presentation ? mb_strtolower($presentation->name) : mb_strtolower($product->unitLabel());
                    throw new BusinessException("«{$product->name}» se vende por {$how}: la cantidad debe ser entera.");
                }

                $listPrice = $presentation ? (float) $presentation->price : (float) $product->sale_price;
                $price     = $this->specialPrice($product, $listPrice, $item['price'], $company);

                $lines[] = [
                    'product'      => $product,
                    'presentation' => $presentation,
                    'name'         => $presentation ? "{$product->name} ({$presentation->label()})" : $product->name,
                    'quantity'     => $item['quantity'],
                    'units'        => $presentation ? (float) $presentation->units : 1.0,
                    'list_price'   => $listPrice,
                    'price'        => $price,
                    'gross'        => round($listPrice * $item['quantity'], 2),
                    'total'        => round($price * $item['quantity'], 2),
                    'affectation'  => $product->igv_affectation,
                ];
            }

            $lines = $this->applyDiscount($lines, $data, $company);
            $gross = round(array_sum(array_column($lines, 'gross')), 2);

            $totals = $this->tax->calculate($lines);
            $total  = $totals['total'];

            // Efectivo: se cobra redondeado a S/ 0.10; el comprobante mantiene el total exacto
            $rounding = $method === Sale::CASH ? $company->cashRounding($total) : 0.0;

            $customer = $this->customer($companyId, $data, $voucher, $method, $total);
            [$received, $change] = $this->cash($method, $data['amount_received'] ?? null, round($total - $rounding, 2));

            [$series, $number] = DocumentSeries::next($companyId, $voucher);

            $sale = Sale::create([
                'company_id'             => $companyId,
                'cash_register_id'       => $register->id,
                'employee_id'            => $employee->id,
                'client_id'              => $customer['client_id'],
                'voucher_type'           => $voucher,
                'series'                 => $series,
                'number'                 => $number,
                'customer_name'          => $customer['name'],
                'customer_document_type' => $customer['document_type'],
                'customer_document'      => $customer['document'],
                'customer_address'       => $customer['address'],
                'payment_method'         => $method,
                'amount_received'        => $received,
                'change'                 => $change,
                'taxable_amount'         => $totals['taxable'],
                'exonerated_amount'      => $totals['exonerated'],
                'unaffected_amount'      => $totals['unaffected'],
                'gross_total'            => $gross,
                'discount_amount'        => round($gross - $total, 2),
                'igv'                    => $totals['igv'],
                'total'                  => $total,
                'rounding_amount'        => $rounding,
                'credit_balance'         => $method === Sale::CREDIT ? $total : 0,
                'status'                 => Sale::ACTIVE,
                'sunat_status'           => $voucher === Sale::TICKET ? Sale::SUNAT_NOT_APPLICABLE : Sale::SUNAT_PENDING,
            ]);

            foreach ($lines as $i => $line) {
                $product = $line['product'];

                $sale->items()->create([
                    'product_id'      => $product->id,
                    'presentation_id' => $line['presentation']?->id,
                    'product_name'    => $line['name'],
                    'unit'            => $product->unit,
                    'quantity'        => $line['quantity'],
                    'units_per_item'  => $line['units'],
                    'list_price'      => $line['list_price'],
                    'unit_price'      => $line['price'],
                    'total'           => $totals['lines'][$i]['total'],
                    'discount'        => round($line['gross'] - $totals['lines'][$i]['total'], 2),
                    'igv_affectation' => $line['affectation'],
                    'base'            => $totals['lines'][$i]['base'],
                    'igv'             => $totals['lines'][$i]['igv'],
                ]);

                // 2 cajas de 12 descuentan 24 del stock
                $this->stock->move($product->id, -round($line['quantity'] * $line['units'], 3), StockMovement::SALE, $sale->id, $sale->voucherNumber());
            }

            AuditLog::record('sale.created',
                "Vendió {$sale->voucherNumber()} por " . Qty::money($total) . " ({$sale->paymentLabel()})",
                $sale, ['total' => $total, 'pago' => $sale->payment_method, 'productos' => count($lines)]);

            if ((float) $sale->discount_amount > 0) {
                AuditLog::record('sale.discount',
                    "Descontó " . Qty::money($sale->discount_amount) . " en {$sale->voucherNumber()} (precio normal " . Qty::money($gross) . ' → ' . Qty::money($total) . ')',
                    $sale, ['normal' => $gross, 'cobrado' => $total, 'descuento' => (float) $sale->discount_amount]);
            }

            return $sale;
        });
    }

    /**
     * Anula una venta: devuelve el stock y la saca de la caja.
     * Solo mientras su caja siga abierta (después ya se hizo el cuadre).
     *
     * @throws BusinessException
     */
    public function void(Sale $sale, string $reason): void
    {
        DB::transaction(function () use ($sale, $reason) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);

            if ($sale->isVoided()) {
                throw new BusinessException('Esta venta ya estaba anulada.');
            }
            if (!$sale->cashRegister->isOpen()) {
                throw new BusinessException('No se puede anular: la caja de esta venta ya se cerró.');
            }
            if ($sale->sunat_status === Sale::SUNAT_ACCEPTED) {
                throw new BusinessException('Esta ' . mb_strtolower($sale->voucherLabel()) . ' ya fue aceptada por SUNAT: se anula con una nota de crédito.');
            }
            if ($sale->payment_method === Sale::CREDIT && (float) $sale->credit_balance < (float) $sale->total) {
                throw new BusinessException('El cliente ya abonó parte de este fiado. No se puede anular.');
            }

            foreach ($sale->items as $item) {
                $this->stock->move($item->product_id, round((float) $item->quantity * (float) $item->units_per_item, 3), StockMovement::SALE_VOID, $sale->id, $sale->voucherNumber());
            }

            $sale->update([
                'status'         => Sale::VOIDED,
                'voided_at'      => now(),
                'void_reason'    => $reason,
                'credit_balance' => 0,
                // Boleta/factura que aún no se envió: ya no se informa a SUNAT
                'sunat_status'   => $sale->sunat_status === Sale::SUNAT_PENDING ? Sale::SUNAT_NOT_APPLICABLE : $sale->sunat_status,
            ]);

            AuditLog::record('sale.void',
                "Anuló la venta {$sale->voucherNumber()} de " . Qty::money($sale->total) . ": {$reason}",
                $sale, ['total' => (float) $sale->total, 'motivo' => $reason, 'vendió' => $sale->employee?->name]);
        });
    }

    /**
     * Suma las cantidades si el mismo producto (en la misma presentación) viene en varias líneas.
     *
     * @return array<string, array{product_id: int, presentation_id: ?int, quantity: float}>
     */
    private function mergeItems(array $items): array
    {
        $merged = [];
        foreach ($items as $item) {
            $productId      = (int) $item['product_id'];
            $presentationId = !empty($item['presentation_id']) ? (int) $item['presentation_id'] : null;
            $key            = $productId . ':' . ($presentationId ?? 0);

            $merged[$key] ??= ['product_id' => $productId, 'presentation_id' => $presentationId, 'quantity' => 0.0, 'price' => null];
            $merged[$key]['quantity'] = round($merged[$key]['quantity'] + (float) $item['quantity'], 3);

            // Precio rebajado por el cajero (opcional)
            if (isset($item['price']) && $item['price'] !== '' && $item['price'] !== null) {
                $merged[$key]['price'] = round((float) $item['price'], 2);
            }
        }

        if (!$merged) {
            throw new BusinessException('Agrega al menos un producto a la venta.');
        }

        return array_values($merged);
    }

    /**
     * Precio rebajado de una línea ("te lo dejo a S/ 6"). Solo se puede bajar, nunca subir,
     * y solo si la empresa permite descuentos. El tope total lo revisa applyDiscount().
     *
     * @throws BusinessException
     */
    private function specialPrice(Product $product, float $listPrice, ?float $requested, Company $company): float
    {
        if ($requested === null || abs($requested - $listPrice) < 0.005) {
            return $listPrice;
        }
        if ($requested <= 0) {
            throw new BusinessException("El precio de «{$product->name}» debe ser mayor que cero.");
        }
        if ($requested > $listPrice) {
            throw new BusinessException("No se puede cobrar «{$product->name}» más caro que su precio (" . Qty::money($listPrice) . ').');
        }
        if (!$company->allow_discounts) {
            throw new BusinessException('La tienda no permite rebajar precios. Pídele al encargado que lo active en Configuración.');
        }

        return $requested;
    }

    /**
     * Descuento a toda la venta (en soles o en %): se reparte entre las líneas según su importe
     * y la última absorbe el centavo que sobre. Luego revisa el tope de la empresa sobre el
     * total de rebajas + descuento.
     *
     * @throws BusinessException
     */
    private function applyDiscount(array $lines, array $data, Company $company): array
    {
        $subtotal = round(array_sum(array_column($lines, 'total')), 2);
        $amount   = round((float) ($data['discount_amount'] ?? 0), 2);
        $percent  = (float) ($data['discount_percent'] ?? 0);

        if ($percent > 0) {
            $amount = round($subtotal * $percent / 100, 2);
        }

        if ($amount > 0) {
            if (!$company->allow_discounts) {
                throw new BusinessException('La tienda no permite descuentos. Pídele al encargado que lo active en Configuración.');
            }
            if ($amount >= $subtotal) {
                throw new BusinessException('El descuento no puede ser igual o mayor que el total de la venta.');
            }

            $lastKey   = array_key_last($lines);
            $allocated = 0.0;
            foreach ($lines as $key => &$line) {
                $share = $key === $lastKey
                    ? round($amount - $allocated, 2)
                    : round($amount * $line['total'] / $subtotal, 2);
                $allocated += $share;
                $line['total'] = round($line['total'] - $share, 2);
            }
            unset($line);
        }

        // Tope: rebajas + descuento no pasan del % que permite la empresa
        $gross    = round(array_sum(array_column($lines, 'gross')), 2);
        $discount = round($gross - array_sum(array_column($lines, 'total')), 2);
        $max      = round($gross * $company->max_discount_percent / 100, 2);

        if ($discount > $max + 0.001) {
            throw new BusinessException('El descuento máximo es ' . $company->max_discount_percent . '% de la venta (' . Qty::money($max) . '). '
                . 'Estás descontando ' . Qty::money($discount) . '.');
        }

        return $lines;
    }

    /**
     * Datos del cliente según el comprobante y la forma de pago.
     * Si trae documento y no existe, se guarda para la próxima vez.
     *
     * @return array{client_id: ?int, name: ?string, document_type: ?string, document: ?string, address: ?string}
     */
    private function customer(int $companyId, array $data, string $voucher, string $method, float $total): array
    {
        $client = null;
        if (!empty($data['client_id'])) {
            $client = Client::where('company_id', $companyId)->find($data['client_id']);
        }

        $name     = trim((string) ($data['customer_name'] ?? '')) ?: $client?->name;
        $document = preg_replace('/\D/', '', (string) ($data['customer_document'] ?? '')) ?: $client?->document_number;
        $address  = trim((string) ($data['customer_address'] ?? '')) ?: $client?->address;
        $docType  = match (strlen((string) $document)) {
            8       => 'dni',
            11      => 'ruc',
            0       => null,
            default => throw new BusinessException('El documento debe ser un DNI (8 números) o un RUC (11 números).'),
        };

        if ($voucher === Sale::FACTURA) {
            if ($docType !== 'ruc') {
                throw new BusinessException('Para factura escribe el RUC del cliente (11 números).');
            }
            if (!$name) {
                throw new BusinessException('Para factura escribe la razón social del cliente.');
            }
        }

        if ($voucher === Sale::BOLETA && $total >= self::BOLETA_ID_LIMIT && (!$document || !$name)) {
            throw new BusinessException('Las boletas desde ' . Qty::money(self::BOLETA_ID_LIMIT) . ' necesitan el nombre y DNI del cliente.');
        }

        if ($method === Sale::CREDIT && !$client && !$name) {
            throw new BusinessException('Para fiar, elige al cliente o escribe su nombre.');
        }

        // Cliente nuevo con datos: se guarda (por documento si lo tiene, si no por nombre)
        if (!$client && $name && ($document || $method === Sale::CREDIT)) {
            $client = $document
                ? Client::firstOrCreate(
                    ['company_id' => $companyId, 'document_number' => $document],
                    ['name' => $name, 'document_type' => $docType, 'address' => $address, 'phone' => $data['customer_phone'] ?? null],
                )
                : Client::create(['company_id' => $companyId, 'name' => $name, 'phone' => $data['customer_phone'] ?? null]);
        }

        return [
            'client_id'     => $client?->id,
            'name'          => $name,
            'document_type' => $docType,
            'document'      => $document,
            'address'       => $address,
        ];
    }

    /**
     * Efectivo: con cuánto pagó y el vuelto. Si no se indica, se asume pago exacto.
     *
     * @return array{0: ?float, 1: ?float}
     */
    private function cash(string $method, mixed $received, float $total): array
    {
        if ($method !== Sale::CASH) {
            return [null, null];
        }

        $received = $received === null || $received === '' ? $total : round((float) $received, 2);

        if ($received < $total) {
            throw new BusinessException('El cliente pagó ' . Qty::money($received) . ' y el total es ' . Qty::money($total) . '. Falta ' . Qty::money($total - $received) . '.');
        }

        return [$received, round($received - $total, 2)];
    }
}
