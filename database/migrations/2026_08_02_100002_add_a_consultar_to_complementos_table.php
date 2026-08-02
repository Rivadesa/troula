<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complementos', function (Blueprint $table) {
            // Precio variable (mesas dulces, puestos de comida, detalles por tramos):
            // se muestra en el configurador como "a consultar" y NO suma al total.
            $table->boolean('a_consultar')->default(false)->after('precio');
        });
    }

    public function down(): void
    {
        Schema::table('complementos', function (Blueprint $table) {
            $table->dropColumn('a_consultar');
        });
    }
};
