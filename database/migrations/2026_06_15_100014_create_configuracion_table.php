<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de fila única (singleton) con los datos de la empresa.
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->default('Troula Eventos');
            $table->string('eslogan')->nullable();
            $table->string('logo')->nullable();

            // Contacto
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('web')->nullable();

            // Dirección / fiscal
            $table->string('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('cif')->nullable();

            // Redes sociales
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('youtube')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
