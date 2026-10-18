<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de ejemplo. Las cuentas de acceso (admin, empresa, empleado) ya las crean las migraciones.
     * Uso: php artisan db:seed
     */
    public function run(): void
    {
        $this->call(ProductSeeder::class);
    }
}
