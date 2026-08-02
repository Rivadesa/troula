<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiencias', function (Blueprint $table) {
            // Horas incluidas en el precio_base (el estándar de Retrátate son 3 h).
            $table->unsignedInteger('duracion_horas')->default(3)->after('precio_base');
            // Precio de cada hora extra. NULL = esta experiencia no admite horas extra.
            $table->decimal('precio_hora_extra', 10, 2)->nullable()->after('duracion_horas');
        });
    }

    public function down(): void
    {
        Schema::table('experiencias', function (Blueprint $table) {
            $table->dropColumn(['duracion_horas', 'precio_hora_extra']);
        });
    }
};
