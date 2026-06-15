<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->string('referencia')->unique();

            $table->foreignId('experiencia_id')->constrained('experiencias')->restrictOnDelete();
            $table->foreignId('pack_id')->nullable()->constrained('packs')->nullOnDelete();

            $table->date('fecha_evento');
            // turno: manana | tarde | completo (App\Enums\Turno)
            $table->string('turno')->default('completo');

            $table->string('concello');
            $table->foreignId('zona_id')->nullable()->constrained('zonas_porte')->nullOnDelete();

            // Datos del cliente
            $table->string('cliente_nombre');
            $table->string('cliente_email');
            $table->string('cliente_telefono');

            // Datos del evento
            $table->string('lugar_evento')->nullable();
            $table->text('observaciones')->nullable();

            // Importes congelados al confirmar
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('ajuste_temporada', 10, 2)->default(0);
            $table->decimal('total_complementos', 10, 2)->default(0);
            $table->decimal('porte', 10, 2)->default(0);
            $table->decimal('montaje', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            // estado: solicitada | confirmada | pagada | realizada | cancelada (App\Enums\EstadoReserva)
            $table->string('estado')->default('solicitada');

            $table->timestamps();

            // Acelera las consultas de disponibilidad por experiencia/fecha/estado.
            $table->index(['experiencia_id', 'fecha_evento', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
