<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concello_zona', function (Blueprint $table) {
            // Provincia del concello: agrupa el desplegable del configurador y
            // permite filtrar en el panel. Nullable por los datos ya existentes.
            $table->string('provincia')->nullable()->after('concello')->index();
        });
    }

    public function down(): void
    {
        Schema::table('concello_zona', function (Blueprint $table) {
            $table->dropIndex(['provincia']);
            $table->dropColumn('provincia');
        });
    }
};
