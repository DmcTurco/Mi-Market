<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\MyApp;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // request()->routeType(): panel al que pertenece la URL actual (admin, company, employee o null).
        // Lo usa FortifyServiceProvider para elegir el guard, el login y el home de cada panel.
        $requestUri = $this->app->request->getRequestUri();
        Request::macro('routeType', function () use ($requestUri) {
            foreach ([MyApp::ADMINS_SUBDIR, MyApp::COMPANY_SUBDIR, MyApp::EMPLOYEE_SUBDIR] as $panel) {
                if (preg_match("#^/{$panel}/#", $requestUri)) {
                    return $panel;
                }
            }

            return null;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fechas en español: "lunes 18 de octubre"
        Carbon::setLocale(config('app.locale'));

        // Bitácora de ingresos al sistema (los 3 paneles). Los intentos fallidos se registran en
        // FortifyServiceProvider, que sabe a qué cuenta apuntaban.
        $panels = [MyApp::ADMINS_SUBDIR => 'admin', MyApp::COMPANY_SUBDIR => 'empresa', MyApp::EMPLOYEE_SUBDIR => 'empleado'];

        Event::listen(Login::class, function (Login $event) use ($panels) {
            AuditLog::record('login', 'Entró al panel de ' . ($panels[$event->guard] ?? $event->guard), $event->user, actor: $event->user);
        });

        Event::listen(Logout::class, function (Logout $event) use ($panels) {
            if ($event->user) {
                AuditLog::record('logout', 'Salió del panel de ' . ($panels[$event->guard] ?? $event->guard), $event->user, actor: $event->user);
            }
        });
    }
}
