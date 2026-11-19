<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['emprendedor', 'comercio', 'admin'])->default('emprendedor')->after('email');
            $table->string('telefono')->nullable()->after('role');
            $table->boolean('autoriza_publicar_contacto')->default(false)->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'telefono', 'autoriza_publicar_contacto']);
        });
    }
};