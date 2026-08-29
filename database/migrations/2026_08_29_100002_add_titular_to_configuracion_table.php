<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            // Persona física que firma los contratos. El contrato de prestación
            // de servicios necesita nombre y DNI del titular, que no siempre
            // coinciden con el nombre comercial ni con el CIF de la empresa.
            $table->string('titular_nombre')->nullable()->after('cif');
            $table->string('titular_dni')->nullable()->after('titular_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn(['titular_nombre', 'titular_dni']);
        });
    }
};
