<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporadas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // Los rangos se repiten cada año; el motor de precios compara mes/día (ver CalculadoraPrecioService).
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            // tipo_ajuste: porcentaje | fijo (App\Enums\TipoAjuste)
            $table->string('tipo_ajuste')->default('porcentaje');
            $table->decimal('valor', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporadas');
    }
};
