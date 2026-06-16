<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            // Contenido HTML de la política de privacidad (editable desde el panel).
            $table->longText('politica_privacidad')->nullable()->after('politica_privacidad_url');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn('politica_privacidad');
        });
    }
};
