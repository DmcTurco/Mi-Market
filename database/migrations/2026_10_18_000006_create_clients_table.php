<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clientes de la tienda: los que compran fiado o piden boleta/factura con sus datos.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);                                  // nombre completo o razón social
            $table->string('document_type', 3)->nullable();               // dni | ruc | null (sin documento)
            $table->string('document_number', 15)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 200)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
