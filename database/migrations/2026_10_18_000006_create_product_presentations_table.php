<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Otras formas de vender un producto: la cerveza por unidad, media caja (6) o caja (12).
     * El stock siempre se guarda en la unidad base del producto; vender 1 caja descuenta 12.
     */
    public function up(): void
    {
        Schema::create('product_presentations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('name', 50);                                   // "Caja", "Media caja", "Six pack"
            $table->decimal('units', 12, 3);                              // cuántas unidades base trae (12, 6...)
            $table->decimal('price', 10, 2);                              // precio de la presentación (suele ser menor que units × precio)
            $table->string('barcode', 30)->nullable();                    // código de la caja (distinto al de la botella)
            $table->timestamps();
            $table->softDeletes();

            // Al eliminarla se libera su código (barcode = null)
            $table->unique(['company_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_presentations');
    }
};
