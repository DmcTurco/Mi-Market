<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Redirección tras iniciar sesión: al home del panel o a la URL que se quería abrir.
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        // La sesión es compartida entre paneles: si la URL pendiente es de otro panel
        // (p. ej. /admin/home al entrar como company) se descarta para no caer en otro login
        $intended = $request->session()->pull('url.intended');
        $panelUrl = url(config('fortify.prefix')) . '/';
        $target   = ($intended && str_starts_with($intended, $panelUrl)) ? $intended : config('fortify.home');

        return redirect()->to($target);
    }
}
