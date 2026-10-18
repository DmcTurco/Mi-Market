<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Llegó mercadería" (ingresos de stock) y el historial de cada movimiento de stock.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();      // null = lo registró la empresa
            $table->unsignedBigInteger('cash_movement_id')->nullable()->index(); // si se pagó con dinero de la caja
            $table->string('supplier', 150)->nullable();                  // quién la trajo (texto libre)
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status', 10)->default('active');              // active | voided (registrada por error)
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            // Tal como se escribió (para poder editarlo): "2 cajas a S/ 60"
            $table->unsignedBigInteger('presentation_id')->nullable()->index();
            $table->decimal('entered_quantity', 12, 3);                   // 2 (cajas)
            $table->decimal('entered_cost', 10, 2);                       // 60.00 (por caja)
            // En unidades del producto (lo que movió el stock): 24 a S/ 5.00
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('reason', 15);                                 // sale | sale_void | purchase | purchase_void | purchase_edit | adjustment
            $table->unsignedBigInteger('reference_id')->nullable();       // id de la venta o del ingreso
            $table->decimal('quantity', 12, 3);                           // + entra, − sale
            $table->decimal('balance', 12, 3);                            // stock que quedó
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
