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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('active')->default(true);   // false = no puede iniciar sesión
            $table->rememberToken();
            $table->timestamps();
        });

        // Primer empleado de la empresa de prueba. Correo y contraseña: INITIAL_EMPLOYEE_* del .env
        DB::table('employees')->insert(InitialUser::attributes('employee') + [
            'company_id' => 1,
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
