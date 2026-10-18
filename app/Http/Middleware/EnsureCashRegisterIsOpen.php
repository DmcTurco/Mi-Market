<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sin caja abierta no se vende: lleva al empleado a abrirla.
 * La caja abierta queda disponible en $request->attributes->get('cash_register').
 */
class EnsureCashRegisterIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $register = auth()->guard('employee')->user()?->openCashRegister();

        if (!$register) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu caja está cerrada. Ábrela para continuar.'], 422);
            }

            return redirect()->route('employee.cash.open')
                ->with('error', 'Primero abre tu caja.');
        }

        $request->attributes->set('cash_register', $register);

        return $next($request);
    }
}
