<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('foto_url')->nullable();
            $table->enum('tipo', ['tienda', 'minimercado', 'supermercado']);
            $table->string('direccion');
            $table->string('ciudad');
            $table->string('sector')->nullable();
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->enum('recepcion_proveedores', ['recibe', 'no_recibe', 'por_confirmar'])->default('por_confirmar');
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_telefono')->nullable();
            $table->string('medio_contacto_autorizado')->nullable();
            $table->string('horario_atencion')->nullable();
            $table->text('requisitos_ingreso')->nullable();
            $table->text('condiciones_compra')->nullable();
            $table->enum('decision_compra', ['sucursal', 'oficina_central'])->default('sucursal');
            $table->string('contacto_oficina_central')->nullable();
            $table->boolean('verificado')->default(false);
            $table->timestamp('ultima_verificacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};