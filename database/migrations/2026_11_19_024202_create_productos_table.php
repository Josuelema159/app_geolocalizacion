<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('nombre');
            $table->string('foto_url')->nullable();
            $table->text('descripcion');
            $table->string('presentacion');
            $table->decimal('precio_comercio', 10, 2);
            $table->decimal('precio_sugerido', 10, 2);
            $table->string('pedido_minimo');
            $table->string('capacidad_semanal');
            $table->string('vida_util')->nullable();
            $table->string('condiciones_conservacion')->nullable();
            $table->string('zonas_entrega');
            $table->string('frecuencia_entrega');
            $table->string('documentos_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};