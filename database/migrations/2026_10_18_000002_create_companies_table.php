<?php

use App\Support\InitialUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ruc', 11)->nullable()->unique();
            $table->string('address', 200)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Configuración de ventas
            $table->boolean('cash_rounding')->default(true);                       // redondear el efectivo a S/ 0.10
            $table->string('rounding_favor', 10)->default('customer');            // customer (hacia abajo) | business (hacia arriba)
            $table->boolean('allow_discounts')->default(true);                    // el cajero puede rebajar precios y dar descuentos
            $table->unsignedTinyInteger('max_discount_percent')->default(20);     // tope de descuento sobre la venta

            $table->rememberToken();
            $table->timestamps();
        });

        // Empresa de prueba (id = 1). Correo y contraseña: INITIAL_COMPANY_* del .env
        DB::table('companies')->insert(InitialUser::attributes('company') + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
