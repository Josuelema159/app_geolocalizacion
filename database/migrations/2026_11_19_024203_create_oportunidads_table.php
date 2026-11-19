<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oportunidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emprendedor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('establecimiento_id')->constrained('establecimientos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->enum('estado', [
                'por_contactar',
                'contactado',
                'muestra_entregada',
                'en_negociacion',
                'primer_pedido',
                'punto_venta_activo',
                'no_concretada'
            ])->default('por_contactar');
            $table->text('mensaje_propuesta')->nullable();
            $table->enum('respuesta_comercio', [
                'pendiente',
                'me_interesa',
                'solicito_informacion',
                'agendar_visita',
                'no_interesado'
            ])->default('pendiente');
            $table->text('notas_privadas')->nullable();
            $table->date('proxima_gestion')->nullable();
            $table->text('condiciones_acordadas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oportunidades');
    }
};