<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiencia_complemento', function (Blueprint $table) {
            // Complementos con el mismo `grupo` dentro de una experiencia se presentan
            // como "elige uno" (radio) en el configurador. NULL = selección libre.
            $table->string('grupo')->nullable()->after('complemento_id');
        });
    }

    public function down(): void
    {
        Schema::table('experiencia_complemento', function (Blueprint $table) {
            $table->dropColumn('grupo');
        });
    }
};
