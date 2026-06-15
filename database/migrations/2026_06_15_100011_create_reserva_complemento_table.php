<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Líneas de complementos seleccionados, con precio congelado.
        // IMPORTANTE: nunca recalcular el total de una reserva a partir de precios vivos.
        Schema::create('reserva_complemento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            $table->foreignId('complemento_id')->constrained('complementos')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_congelado', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserva_complemento');
    }
};
