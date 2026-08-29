<?php

namespace App\Providers;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->aplicarConfiguracionCorreo();

        // Permite usar el layout público como componente (<x-layouts::configurador>)
        // desde vistas Blade normales, no solo desde Livewire con #[Layout].
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
    }

    /**
     * Aplica la configuración de correo guardada en BD (editable desde el panel),
     * sobreescribiendo la del .env. Tolerante a que la tabla aún no exista
     * (instalaciones nuevas / migraciones).
     */
    private function aplicarConfiguracionCorreo(): void
    {
        try {
            if (! Schema::hasTable('configuracion')) {
                return;
            }

            Configuracion::actual()->aplicarCorreo();
        } catch (\Throwable) {
            // Sin configuración válida: se mantiene la del .env.
        }
    }
}
