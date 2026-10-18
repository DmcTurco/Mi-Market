<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\CashRegister;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Employee;
use App\Support\Qty;
use Illuminate\Support\Facades\DB;

/**
 * Abonos del fiado: el pago se aplica a las ventas fiadas más antiguas primero.
 */
class CreditService
{
    /**
     * @throws BusinessException
     */
    public function pay(Client $client, float $amount, string $method, ?Employee $employee = null, ?CashRegister $register = null): CreditPayment
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new BusinessException('Escribe cuánto está pagando.');
        }
        if ($register && !$register->isOpen()) {
            throw new BusinessException('Tu caja está cerrada. Ábrela para recibir pagos.');
        }

        return DB::transaction(function () use ($client, $amount, $method, $employee, $register) {
            // La más antigua primero (el id desempata ventas del mismo segundo)
            $sales = $client->pendingSales()->orderBy('created_at')->orderBy('id')->lockForUpdate()->get();
            $debt  = round((float) $sales->sum('credit_balance'), 2);

            if ($debt <= 0) {
                throw new BusinessException("{$client->name} no tiene deudas pendientes.");
            }
            if ($amount > $debt) {
                throw new BusinessException("{$client->name} debe " . Qty::money($debt) . '. No puede pagar más que eso.');
            }

            $left = $amount;
            foreach ($sales as $sale) {
                if ($left <= 0) {
                    break;
                }
                $applied = min($left, (float) $sale->credit_balance);
                $sale->update(['credit_balance' => round((float) $sale->credit_balance - $applied, 2)]);
                $left = round($left - $applied, 2);
            }

            return CreditPayment::create([
                'company_id'       => $client->company_id,
                'client_id'        => $client->id,
                'cash_register_id' => $register?->id,
                'employee_id'      => $employee?->id,
                'amount'           => $amount,
                'payment_method'   => $method,
            ]);
        });
    }
}
