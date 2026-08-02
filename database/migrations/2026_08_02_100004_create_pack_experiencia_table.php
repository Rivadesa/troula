<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bases (máquinas) intercambiables de un pack: el cliente puede cambiar el
        // fotomatón incluido pagando un suplemento. La base por defecto sigue siendo
        // `packs.experiencia_id` (suplemento 0 si no está en esta tabla).
        Schema::create('pack_experiencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained('packs')->cascadeOnDelete();
            $table->foreignId('experiencia_id')->constrained('experiencias')->cascadeOnDelete();
            $table->decimal('suplemento', 10, 2)->default(0);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['pack_id', 'experiencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_experiencia');
    }
};
