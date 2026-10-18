<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('barcode', 30)->nullable();                     // código de barras (lo lee la pistola)
            $table->string('name', 150);
            $table->string('unit', 10)->default('unidad');                 // unidad | kg | litro | paquete | caja (ver Product::UNITS)
            $table->decimal('purchase_price', 10, 2)->default(0);          // lo que costó la última vez (por unidad)
            $table->decimal('sale_price', 10, 2);                          // precio final que paga el cliente (IGV incluido)
            $table->decimal('stock', 12, 3)->default(0);                   // 3 decimales: se vende por kilo o litro
            $table->decimal('min_stock', 12, 3)->default(0);               // aviso de "quedan pocos"
            $table->string('igv_affectation', 2)->default('10');           // SUNAT catálogo 07: 10 gravado, 20 exonerado, 30 inafecto
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'barcode']);
            $table->index(['company_id', 'active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
