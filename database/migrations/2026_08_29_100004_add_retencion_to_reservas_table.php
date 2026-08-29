<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Hasta cuándo esta reserva sin pagar bloquea la fecha. Pasada esa
            // hora deja de contar para la disponibilidad y el equipo vuelve a
            // estar libre. Se pone a NULL en cuanto se cobra la señal.
            $table->timestamp('reserva_expira_en')->nullable()->after('estado')->index();
        });

        Schema::table('configuracion', function (Blueprint $table) {
            // Minutos que se aguanta la fecha mientras el cliente firma y paga.
            // 0 = sin caducidad (la reserva bloquea desde que se crea).
            $table->unsignedInteger('reserva_minutos_retencion')->default(60);
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['reserva_expira_en']);
            $table->dropColumn('reserva_expira_en');
        });

        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn('reserva_minutos_retencion');
        });
    }
};
