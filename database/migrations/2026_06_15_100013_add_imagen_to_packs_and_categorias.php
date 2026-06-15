<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('descripcion');
        });

        Schema::table('categorias_complemento', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });

        Schema::table('categorias_complemento', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
