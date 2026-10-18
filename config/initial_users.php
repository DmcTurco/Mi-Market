<?php

/*
|--------------------------------------------------------------------------
| Cuentas iniciales que crean las migraciones
|--------------------------------------------------------------------------
| Admin del sistema, una empresa de prueba y su primer empleado.
| Las contraseñas NO van en el código: se leen del .env. En producción son
| obligatorias (mínimo 10 caracteres, con letras y números); en local, si
| faltan, se usa la contraseña de desarrollo.
*/

return [

    // Contraseña que se usa en local cuando el .env no define una
    'dev_password' => 'Market#2026',

    'admin' => [
        'name'     => env('INITIAL_ADMIN_NAME', 'Administrador'),
        'email'    => env('INITIAL_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],

    'company' => [
        'name'     => env('INITIAL_COMPANY_NAME', 'Mi Tienda'),
        'email'    => env('INITIAL_COMPANY_EMAIL', 'company@example.com'),
        'password' => env('INITIAL_COMPANY_PASSWORD'),
    ],

    'employee' => [
        'name'     => env('INITIAL_EMPLOYEE_NAME', 'Cajero'),
        'email'    => env('INITIAL_EMPLOYEE_EMAIL', 'employee@example.com'),
        'password' => env('INITIAL_EMPLOYEE_PASSWORD'),
    ],

];
