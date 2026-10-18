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
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete(); // null = lo registró la empresa
            $table->foreignId('cash_movement_id')->nullable()->constrained()->nullOnDelete(); // si se pagó con dinero de la caja
            $table->string('supplier', 150)->nullable();                  // quién la trajo (texto libre)
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status', 10)->default('active');              // active | voided (registrada por error)
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 15);                                 // sale | sale_void | purchase | adjustment
            $table->unsignedBigInteger('reference_id')->nullable();       // id de la venta o del ingreso
            $table->decimal('quantity', 12, 3);                           // + entra, − sale
            $table->decimal('balance', 12, 3);                            // stock que quedó
            $table->string('notes', 255)->nullable();
            $table->timestamps();

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
