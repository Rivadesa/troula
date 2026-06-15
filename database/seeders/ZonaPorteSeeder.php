<?php

namespace Database\Seeders;

use App\Models\ConcelloZona;
use App\Models\ZonaPorte;
use Illuminate\Database\Seeder;

class ZonaPorteSeeder extends Seeder
{
    public function run(): void
    {
        // DECISIÓN: v1 calcula porte/montaje por zona (no por km vía API).
        // El cliente afinará precios y reasignará concellos desde el panel.
        $zonaA = ZonaPorte::updateOrCreate(['nombre' => 'Zona A · A Coruña ciudad y área'], [
            'precio_porte' => 0,
            'precio_montaje' => 30,
        ]);
        $zonaB = ZonaPorte::updateOrCreate(['nombre' => 'Zona B · Área metropolitana'], [
            'precio_porte' => 40,
            'precio_montaje' => 40,
        ]);
        $zonaC = ZonaPorte::updateOrCreate(['nombre' => 'Zona C · Resto de la provincia'], [
            'precio_porte' => 80,
            'precio_montaje' => 50,
        ]);

        $zonaA_concellos = [
            'A Coruña', 'Arteixo', 'Culleredo', 'Cambre', 'Oleiros', 'Sada', 'Bergondo',
        ];

        $zonaB_concellos = [
            'Abegondo', 'Betanzos', 'Carral', 'Miño', 'Paderne', 'Carballo', 'A Laracha',
            'Ordes', 'Cerceda', 'Oza-Cesuras', 'Coirós',
        ];

        // El resto de concellos de la provincia se asignan por defecto a la Zona C.
        $restoProvincia = [
            'Ames', 'Aranga', 'Ares', 'Arzúa', 'A Baña', 'Boimorto', 'Boiro', 'Boqueixón',
            'Brión', 'Cabana de Bergantiños', 'Cabanas', 'Camariñas', 'A Capela', 'Cariño',
            'Carnota', 'Cedeira', 'Cee', 'Cerdido', 'Corcubión', 'Coristanco', 'Curtis',
            'Dodro', 'Dumbría', 'Fene', 'Ferrol', 'Fisterra', 'Frades', 'Irixoa', 'Laxe',
            'Lousame', 'Malpica de Bergantiños', 'Mañón', 'Mazaricos', 'Melide', 'Mesía',
            'Moeche', 'Monfero', 'Mugardos', 'Muros', 'Muxía', 'Narón', 'Neda', 'Negreira',
            'Noia', 'Oroso', 'Ortigueira', 'Outes', 'Padrón', 'O Pino', 'A Pobra do Caramiñal',
            'Ponteceso', 'Pontedeume', 'As Pontes de García Rodríguez', 'Porto do Son',
            'Rianxo', 'Ribeira', 'Rois', 'San Sadurniño', 'Santa Comba',
            'Santiago de Compostela', 'Santiso', 'Sobrado', 'As Somozas', 'Teo', 'Toques',
            'Tordoia', 'Touro', 'Trazo', 'Val do Dubra', 'Valdoviño', 'Vedra', 'Vilarmaior',
            'Vilasantar', 'Vimianzo', 'Zas',
        ];

        $this->asignar($zonaA_concellos, $zonaA->id);
        $this->asignar($zonaB_concellos, $zonaB->id);
        $this->asignar($restoProvincia, $zonaC->id);
    }

    /**
     * @param  array<int, string>  $concellos
     */
    private function asignar(array $concellos, int $zonaId): void
    {
        foreach ($concellos as $concello) {
            ConcelloZona::updateOrCreate(['concello' => $concello], ['zona_id' => $zonaId]);
        }
    }
}
