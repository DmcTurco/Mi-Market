<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ventas del punto de venta, su detalle, la numeración de comprobantes y los abonos del fiado.
     */
    public function up(): void
    {
        // Correlativo de cada tipo de comprobante por empresa: T001-00000001, B001-..., F001-...
        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('voucher_type', 10);                           // ticket | boleta | factura
            $table->string('series', 4);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'voucher_type']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $table->string('voucher_type', 10);                           // ticket | boleta | factura
            $table->string('series', 4);
            $table->unsignedBigInteger('number');
            $table->string('customer_name', 150)->nullable();             // datos del cliente tal como salieron en el comprobante
            $table->string('customer_document_type', 3)->nullable();      // dni | ruc
            $table->string('customer_document', 15)->nullable();
            $table->string('customer_address', 200)->nullable();

            $table->string('payment_method', 10);                         // efectivo | yape | plin | tarjeta | fiado
            $table->decimal('amount_received', 10, 2)->nullable();        // con cuánto pagó (efectivo)
            $table->decimal('change', 10, 2)->nullable();                 // vuelto

            $table->decimal('taxable_amount', 10, 2)->default(0);         // base gravada (sin IGV)
            $table->decimal('exonerated_amount', 10, 2)->default(0);
            $table->decimal('unaffected_amount', 10, 2)->default(0);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total', 10, 2);                              // lo que pagó el cliente
            $table->decimal('credit_balance', 10, 2)->default(0);         // lo que aún debe (fiado)

            $table->string('status', 10)->default('active');              // active | voided
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();

            // Facturación electrónica (boleta y factura)
            $table->string('sunat_status', 15)->default('not_applicable'); // not_applicable | pending | accepted | rejected | error
            $table->string('sunat_code', 10)->nullable();
            $table->text('sunat_message')->nullable();
            $table->string('sunat_hash', 64)->nullable();
            $table->string('sunat_xml_path')->nullable();
            $table->string('sunat_cdr_path')->nullable();
            $table->unsignedSmallInteger('sunat_attempts')->default(0);
            $table->timestamp('sunat_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'series', 'number']);
            $table->index(['company_id', 'created_at']);
            $table->index(['client_id', 'credit_balance']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 150);
            $table->string('unit', 10);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 10, 2);                         // precio final por unidad (IGV incluido)
            $table->decimal('total', 10, 2);                              // cantidad × precio
            $table->string('igv_affectation', 2);
            $table->decimal('base', 10, 2);                               // total sin IGV
            $table->decimal('igv', 10, 2)->default(0);
            $table->timestamps();
        });

        // Abonos del fiado: el cliente paga parte o toda su deuda
        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 10);                         // efectivo | yape | plin | tarjeta
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('document_series');
    }
};
