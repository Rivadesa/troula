<?php

namespace Database\Seeders;

use App\Models\ConcelloZona;
use App\Models\ZonaPorte;
use Illuminate\Database\Seeder;

/**
 * Zonas de porte y los 313 concellos de Galicia, agrupados por provincia.
 *
 * // DECISIÓN: v1 calcula porte/montaje por zona (no por km vía API).
 * El cliente afina precios y reasigna concellos desde el panel.
 *
 * // PENDIENTE DE CONFIRMAR CON EL CLIENTE: los precios de las zonas de Lugo,
 * Ourense y Pontevedra son una propuesta inicial (escalan con la distancia desde
 * A Coruña). Se editan en Panel → Zonas de porte sin tocar código.
 *
 * Idempotente: si un concello ya existe, solo se le completa la provincia y se
 * respeta la zona que tenga asignada (el admin puede haberla cambiado a mano).
 */
class ZonaPorteSeeder extends Seeder
{
    public function run(): void
    {
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
        $zonaPontevedra = ZonaPorte::updateOrCreate(['nombre' => 'Zona D · Provincia de Pontevedra'], [
            'precio_porte' => 100,
            'precio_montaje' => 50,
        ]);
        $zonaLugo = ZonaPorte::updateOrCreate(['nombre' => 'Zona E · Provincia de Lugo'], [
            'precio_porte' => 110,
            'precio_montaje' => 50,
        ]);
        $zonaOurense = ZonaPorte::updateOrCreate(['nombre' => 'Zona F · Provincia de Ourense'], [
            'precio_porte' => 140,
            'precio_montaje' => 50,
        ]);

        // --- A CORUÑA (93) ---------------------------------------------------
        $this->asignar([
            'A Coruña', 'Arteixo', 'Culleredo', 'Cambre', 'Oleiros', 'Sada', 'Bergondo',
        ], $zonaA->id, 'A Coruña');

        $this->asignar([
            'Abegondo', 'Betanzos', 'Carral', 'Miño', 'Paderne', 'Carballo', 'A Laracha',
            'Ordes', 'Cerceda', 'Oza-Cesuras', 'Coirós',
        ], $zonaB->id, 'A Coruña');

        $this->asignar([
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
        ], $zonaC->id, 'A Coruña');

        // --- LUGO (67) -------------------------------------------------------
        $this->asignar([
            'Abadín', 'Alfoz', 'Antas de Ulla', 'Baleira', 'Baralla', 'Barreiros', 'Becerreá',
            'Begonte', 'Bóveda', 'Burela', 'Carballedo', 'Castro de Rei', 'Castroverde',
            'Cervantes', 'Cervo', 'Chantada', 'O Corgo', 'Cospeito', 'Folgoso do Courel',
            'A Fonsagrada', 'Foz', 'Friol', 'Guitiriz', 'Guntín', 'O Incio', 'Láncara',
            'Lourenzá', 'Lugo', 'Meira', 'Mondoñedo', 'Monforte de Lemos', 'Monterroso',
            'Muras', 'Navia de Suarna', 'Negueira de Muñiz', 'As Nogais', 'Ourol',
            'Outeiro de Rei', 'Palas de Rei', 'Pantón', 'Paradela', 'O Páramo', 'A Pastoriza',
            'Pedrafita do Cebreiro', 'A Pobra do Brollón', 'Pol', 'A Pontenova', 'Portomarín',
            'Quiroga', 'Rábade', 'Ribadeo', 'Ribas de Sil', 'Ribeira de Piquín', 'Riotorto',
            'Samos', 'Sarria', 'O Saviñao', 'Sober', 'Taboada', 'Trabada', 'Triacastela',
            'O Valadouro', 'O Vicedo', 'Vilalba', 'Viveiro', 'Xermade', 'Xove',
        ], $zonaLugo->id, 'Lugo');

        // --- OURENSE (92) ----------------------------------------------------
        $this->asignar([
            'Allariz', 'Amoeiro', 'A Arnoia', 'Avión', 'Baltar', 'Bande', 'Baños de Molgas',
            'Barbadás', 'O Barco de Valdeorras', 'Beade', 'Beariz', 'Os Blancos', 'Boborás',
            'A Bola', 'O Bolo', 'Calvos de Randín', 'Carballeda de Avia',
            'Carballeda de Valdeorras', 'O Carballiño', 'Cartelle', 'Castrelo de Miño',
            'Castrelo do Val', 'Castro Caldelas', 'Celanova', 'Cenlle', 'Chandrexa de Queixa',
            'Coles', 'Cortegada', 'Cualedro', 'Entrimo', 'Esgos', 'Gomesende', 'A Gudiña',
            'O Irixo', 'Larouco', 'Laza', 'Leiro', 'Lobeira', 'Lobios', 'Maceda', 'Manzaneda',
            'Maside', 'Melón', 'A Merca', 'A Mezquita', 'Montederramo', 'Monterrei', 'Muíños',
            'Nogueira de Ramuín', 'Oímbra', 'Ourense', 'Paderne de Allariz', 'Padrenda',
            'Parada de Sil', 'O Pereiro de Aguiar', 'A Peroxa', 'Petín', 'Piñor',
            'A Pobra de Trives', 'Pontedeva', 'Porqueira', 'Punxín', 'Quintela de Leirado',
            'Rairiz de Veiga', 'Ramirás', 'Ribadavia', 'Riós', 'A Rúa', 'Rubiá', 'San Amaro',
            'San Cibrao das Viñas', 'San Cristovo de Cea', 'San Xoán de Río', 'Sandiás',
            'Sarreaus', 'Taboadela', 'A Teixeira', 'Toén', 'Trasmiras', 'A Veiga', 'Verea',
            'Verín', 'Viana do Bolo', 'Vilamarín', 'Vilamartín de Valdeorras',
            'Vilar de Barrio', 'Vilar de Santos', 'Vilardevós', 'Vilariño de Conso',
            'Xinzo de Limia', 'Xunqueira de Ambía', 'Xunqueira de Espadanedo',
        ], $zonaOurense->id, 'Ourense');

        // --- PONTEVEDRA (61) -------------------------------------------------
        $this->asignar([
            'Agolada', 'Arbo', 'Baiona', 'Barro', 'Bueu', 'Caldas de Reis', 'Cambados',
            'Campo Lameiro', 'Cangas', 'A Cañiza', 'Catoira', 'Cerdedo-Cotobade', 'Covelo',
            'Crecente', 'Cuntis', 'Dozón', 'A Estrada', 'Forcarei', 'Fornelos de Montes',
            'Gondomar', 'O Grove', 'A Guarda', 'A Illa de Arousa', 'Lalín', 'A Lama', 'Marín',
            'Meaño', 'Meis', 'Moaña', 'Mondariz', 'Mondariz-Balneario', 'Moraña', 'Mos',
            'As Neves', 'Nigrán', 'Oia', 'Pazos de Borbén', 'Poio', 'Ponte Caldelas',
            'Ponteareas', 'Pontecesures', 'Pontevedra', 'O Porriño', 'Portas', 'Redondela',
            'Ribadumia', 'Rodeiro', 'O Rosal', 'Salceda de Caselas', 'Salvaterra de Miño',
            'Sanxenxo', 'Silleda', 'Soutomaior', 'Tomiño', 'Tui', 'Valga', 'Vigo',
            'Vila de Cruces', 'Vilaboa', 'Vilagarcía de Arousa', 'Vilanova de Arousa',
        ], $zonaPontevedra->id, 'Pontevedra');

        $this->command?->info('Concellos cargados: '.ConcelloZona::count().' de 313.');
    }

    /**
     * @param  array<int, string>  $concellos
     */
    private function asignar(array $concellos, int $zonaId, string $provincia): void
    {
        foreach ($concellos as $concello) {
            $fila = ConcelloZona::firstOrNew(['concello' => $concello]);

            // Solo se asigna zona al crear: si ya existe, se respeta la que tenga
            // (el admin puede haberla reasignado desde el panel).
            if (! $fila->exists) {
                $fila->zona_id = $zonaId;
            }

            $fila->provincia = $provincia;
            $fila->save();
        }
    }
}
