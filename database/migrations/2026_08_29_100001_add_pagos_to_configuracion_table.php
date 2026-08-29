<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            // Señal que se pide al reservar.
            $table->string('senal_tipo')->default('porcentaje');   // porcentaje | fijo
            $table->decimal('senal_valor', 10, 2)->default(30);
            $table->text('senal_texto')->nullable();

            // Métodos de cobro disponibles.
            $table->boolean('pago_transferencia')->default(true);
            $table->string('pago_iban')->nullable();
            $table->string('pago_titular')->nullable();
            $table->boolean('pago_tarjeta')->default(false);

            // Redsys. La clave va cifrada (cast 'encrypted' en el modelo).
            $table->string('redsys_entorno')->default('pruebas');  // pruebas | produccion
            $table->string('redsys_comercio')->nullable();
            $table->string('redsys_terminal')->default('1');
            $table->text('redsys_clave')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'senal_tipo', 'senal_valor', 'senal_texto',
                'pago_transferencia', 'pago_iban', 'pago_titular', 'pago_tarjeta',
                'redsys_entorno', 'redsys_comercio', 'redsys_terminal', 'redsys_clave',
            ]);
        });
    }
};
