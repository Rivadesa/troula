<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivote N:N central: qué complementos ofrece cada experiencia y con qué reglas.
        Schema::create('experiencia_complemento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiencia_id')->constrained('experiencias')->cascadeOnDelete();
            $table->foreignId('complemento_id')->constrained('complementos')->cascadeOnDelete();
            $table->decimal('precio_override', 10, 2)->nullable();
            $table->boolean('obligatorio')->default(false);
            $table->unsignedInteger('cantidad_maxima')->default(1);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['experiencia_id', 'complemento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiencia_complemento');
    }
};
