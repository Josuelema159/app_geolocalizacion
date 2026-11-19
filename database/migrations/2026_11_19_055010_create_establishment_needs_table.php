<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establishment_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 255);
            $table->string('quantity', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('buscando');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establishment_needs');
    }
};
