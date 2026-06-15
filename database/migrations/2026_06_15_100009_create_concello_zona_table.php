<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mapea cada concello de A Coruña a una zona de porte.
        // DECISIÓN: el cálculo por km exacto vía API se deja para una fase posterior;
        // el modelo de zonas debe poder convivir con él después.
        Schema::create('concello_zona', function (Blueprint $table) {
            $table->id();
            $table->string('concello');
            $table->foreignId('zona_id')->constrained('zonas_porte')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->unique('concello');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concello_zona');
    }
};
