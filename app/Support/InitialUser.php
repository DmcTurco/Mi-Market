<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Datos de las cuentas iniciales que insertan las migraciones (admin, company, employee),
 * leídos de config/initial_users.php (.env). Evita contraseñas fijas en el código.
 */
class InitialUser
{
    /**
     * Nombre, correo y contraseña ya cifrada de la cuenta, listos para insertar.
     *
     * @param  string $account admin | company | employee
     * @return array{name: string, email: string, password: string}
     *
     * @throws RuntimeException en producción, si la contraseña falta o es débil
     */
    public static function attributes(string $account): array
    {
        $config   = config("initial_users.{$account}");
        $password = (string) ($config['password'] ?? '');
        $variable = 'INITIAL_' . strtoupper($account) . '_PASSWORD';

        if (app()->isProduction()) {
            if (!self::isStrong($password)) {
                throw new RuntimeException(
                    "Falta {$variable} en el .env o es débil (mínimo 10 caracteres, con letras y números). "
                    . 'Defínela y vuelve a migrar (si usas config:cache, corre antes php artisan config:clear).'
                );
            }
        } elseif ($password === '') {
            $password = config('initial_users.dev_password');
        }

        return [
            'name'     => $config['name'],
            'email'    => $config['email'],
            'password' => Hash::make($password),
        ];
    }

    /**
     * ¿La contraseña tiene al menos 10 caracteres, letras y números?
     */
    public static function isStrong(string $password): bool
    {
        return mb_strlen($password) >= 10
            && preg_match('/\pL/u', $password)
            && preg_match('/\d/', $password);
    }
}
