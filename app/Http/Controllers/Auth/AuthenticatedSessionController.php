<?php

namespace App\Http\Controllers\Auth;

use App\MyApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifyAuthenticatedSessionController;

/**
 * Login/logout de Fortify con un cambio en el cierre de sesión: los tres paneles (admin,
 * company, employee) comparten la misma sesión del navegador. Fortify la invalida entera
 * al salir, lo que cerraba también los otros paneles y dejaba sus pestañas con el token
 * vencido (419 "Page Expired" al siguiente clic). Aquí solo se sale del panel actual.
 */
class AuthenticatedSessionController extends FortifyAuthenticatedSessionController
{
    /**
     * Cierra la sesión del panel actual. Si en el navegador sigue abierto otro panel,
     * la sesión se conserva (con nuevo id y el mismo token); si no, se invalida por completo.
     */
    public function destroy(Request $request): LogoutResponse
    {
        $this->guard->logout();

        if ($request->hasSession()) {
            $otherPanelOpen = collect([MyApp::ADMINS_SUBDIR, MyApp::COMPANY_SUBDIR, MyApp::EMPLOYEE_SUBDIR])
                ->contains(fn (string $guard) => Auth::guard($guard)->check());

            if ($otherPanelOpen) {
                $request->session()->migrate(true);
            } else {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return app(LogoutResponse::class);
    }
}
