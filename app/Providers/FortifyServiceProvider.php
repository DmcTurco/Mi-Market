<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\MyApp;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios de la aplicación.
     */
    public function register(): void
    {
        // Cerrar sesión sale solo del panel actual (ver App\Http\Controllers\Auth\AuthenticatedSessionController)
        $this->app->bind(
            \Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class,
            \App\Http\Controllers\Auth\AuthenticatedSessionController::class,
        );

        // Cada panel tiene su propio login: /admin/login, /company/login, /employee/login
        $routeType = request()->routeType();
        if ($routeType) {
            config([
                'fortify.prefix' => $routeType,
                'fortify.guard'  => $routeType,
                'fortify.home'   => '/' . $routeType . '/home',
                'fortify.login'  => '/' . $routeType . '/login',
                'fortify.logout' => '/' . $routeType . '/logout',
            ]);
        }
    }

    /**
     * Arranca los servicios de la aplicación.
     */
    public function boot(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);

        /**
         * Busca la cuenta en la tabla del panel actual. El email es case-insensitive
         * (PostgreSQL distingue mayúsculas en WHERE email = ?).
         */
        Fortify::authenticateUsing(function (Request $request) {
            $modelMap = [
                MyApp::ADMINS_SUBDIR   => Admin::class,
                MyApp::COMPANY_SUBDIR  => Company::class,
                MyApp::EMPLOYEE_SUBDIR => Employee::class,
            ];

            $modelClass = $modelMap[config('fortify.guard')] ?? null;
            if (!$modelClass) {
                return null;
            }

            $email = strtolower(trim($request->input(Fortify::username(), '')));
            $user  = $modelClass::whereRaw('LOWER(email) = ?', [$email])->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return null;
            }

            // Un empleado dado de baja por su empresa ya no puede entrar
            if ($user instanceof Employee && !$user->active) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'Tu cuenta está desactivada. Consulta con el encargado de la tienda.',
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        Fortify::loginView(function () {
            return match (request()->routeType()) {
                MyApp::ADMINS_SUBDIR   => view('admin.pages.login'),
                MyApp::COMPANY_SUBDIR  => view('company.pages.login'),
                MyApp::EMPLOYEE_SUBDIR => view('employee.pages.login'),
                default                => redirect('/'),
            };
        });
    }
}
