<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas_porte', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('precio_porte', 10, 2)->default(0);
            $table->decimal('precio_montaje', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas_porte');
    }
};
