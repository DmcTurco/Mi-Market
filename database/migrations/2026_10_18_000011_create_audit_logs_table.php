<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora: quién hizo qué, sobre qué y cuándo (ventas, anulaciones, precios, stock, caja, ingresos al sistema...).
     * Es de solo escritura: nunca se edita ni se borra, por eso no lleva softDeletes ni updated_at.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // null = acción del administrador del sistema

            // Quién: se guarda el nombre por si luego eliminan a la persona
            $table->string('actor_type', 10);                             // admin | company | employee | system
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name', 150)->nullable();

            // Qué
            $table->string('action', 40)->index();                        // sale.void, product.updated, login...
            $table->string('subject_type', 40)->nullable();               // Product, Sale...
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label', 150)->nullable();             // "Arroz Costeño 1kg", "T001-00000012"
            $table->string('description', 255);
            $table->json('changes')->nullable();                          // {"sale_price": [4.50, 4.20]} o datos útiles

            // Desde dónde
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
