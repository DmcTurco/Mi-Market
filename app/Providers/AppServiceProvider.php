<?php

namespace App\Providers;

use App\MyApp;
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
    }
}
