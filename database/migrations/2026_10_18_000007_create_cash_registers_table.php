<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Caja de cada turno: el empleado la abre con un monto, vende, y al cerrar cuenta el dinero.
     */
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->decimal('opening_amount', 10, 2)->default(0);         // con cuánto abrió
            $table->decimal('expected_amount', 10, 2)->nullable();        // efectivo que debía haber al cerrar
            $table->decimal('closing_amount', 10, 2)->nullable();         // efectivo que contó al cerrar
            $table->decimal('difference', 10, 2)->nullable();             // contado − esperado (+ sobra, − falta)
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();                   // null = abierta
            $table->timestamps();

            $table->index(['employee_id', 'closed_at']);
        });

        // Gastos (pagó el flete) y otros ingresos (le dieron sencillo) en efectivo de la caja
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);                                   // income | expense
            $table->string('concept', 150);
            $table->decimal('amount', 10, 2);                             // siempre positivo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_registers');
    }
};
