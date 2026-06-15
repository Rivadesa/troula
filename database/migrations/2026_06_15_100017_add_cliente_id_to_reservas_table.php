<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Enlace al registro de cliente. Los datos cliente_* de la reserva siguen
            // siendo la "foto" congelada en el momento de la reserva.
            $table->foreignId('cliente_id')->nullable()->after('referencia')
                ->constrained('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
