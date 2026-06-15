<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fase 1: la tabla y la máquina de estados quedan creadas pero sin integración real de pasarela.
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
            // tipo: senal | saldo (App\Enums\TipoPago)
            $table->string('tipo');
            $table->decimal('importe', 10, 2)->default(0);
            // estado: pendiente | pagado | fallido (App\Enums\EstadoPago)
            $table->string('estado')->default('pendiente');
            $table->string('pasarela')->nullable();
            $table->string('referencia_pasarela')->nullable();
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
