<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            // Plantilla del contrato, editable desde el panel.
            $table->longText('contrato_plantilla')->nullable();
        });

        Schema::table('reservas', function (Blueprint $table) {
            // Datos que el contrato necesita y el configurador no pide.
            $table->string('cliente_dni')->nullable()->after('cliente_telefono');
            $table->string('cliente_direccion')->nullable()->after('cliente_dni');

            // Registro de la aceptación (firma electrónica simple).
            // Se guarda el TEXTO EXACTO aceptado, no solo la plantilla: si el
            // admin la cambia después, el contrato firmado no debe moverse.
            $table->longText('contrato_texto')->nullable();
            $table->string('contrato_hash', 64)->nullable();
            $table->timestamp('contrato_aceptado_en')->nullable();
            $table->string('contrato_ip', 45)->nullable();
            $table->string('contrato_user_agent')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn('contrato_plantilla');
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn([
                'cliente_dni', 'cliente_direccion', 'contrato_texto',
                'contrato_hash', 'contrato_aceptado_en', 'contrato_ip', 'contrato_user_agent',
            ]);
        });
    }
};
