<?php

use App\CategoriaMasaje;
use App\PrecioTipoMasaje;
use App\TipoMasaje;
use Illuminate\Database\Seeder;

class CatalogoMasajesSeeder extends Seeder
{
    public function run()
    {
        $catCorp = CategoriaMasaje::firstOrCreate(['slug'=>'corporales'], ['nombre'=>'Masajes Corporales']);
        $catFace = CategoriaMasaje::firstOrCreate(['slug'=>'faciales'],   ['nombre'=>'Masajes Faciales']);
        $catComp = CategoriaMasaje::firstOrCreate(['slug'=>'terapias'],   ['nombre'=>'Terapias Complementarias']);

        $crear = function($cat, $slug, $nombre, $precios) {
            $tipo = TipoMasaje::firstOrCreate(
                ['slug'=>$slug],
                ['nombre'=>$nombre,'id_categoria_masaje'=>$cat->id,'activo'=>true]
            );
            foreach ($precios as $duracion => $vals) {
                PrecioTipoMasaje::updateOrCreate(
                    ['id_tipo_masaje'=>$tipo->id, 'duracion_minutos'=>$duracion],
                    [
                        'precio_unitario' => $vals['unit'],
                        'precio_pareja'   => isset($vals['pair']) ? $vals['pair'] : null
                    ]
                );
            }
        };

        // Corporales
        $crear($catCorp, 'relajacion',        'Relajación',        [30=>['unit'=>25000,'pair'=>48000], 60=>['unit'=>45000,'pair'=>88000]]);
        $crear($catCorp, 'descontracturante', 'Descontracturante', [30=>['unit'=>30000],                60=>['unit'=>48000]]);
        $crear($catCorp, 'balines',           'Balines',           [60=>['unit'=>45000]]);
        $crear($catCorp, 'prenatal',          'Prenatal',          [60=>['unit'=>45000]]);

        // Faciales
        $crear($catFace, 'craneo_facial',     'Craneo/facial',     [30=>['unit'=>25000]]);
        $crear($catFace, 'cervico_craneal',   'Cérvico-craneal',   [30=>['unit'=>25000]]);
        $crear($catFace, 'champi',            'Champi',            [30=>['unit'=>25000]]);

        // Terapias complementarias
        $crear($catComp, 'terapia_manual_ortopedica',   'Terapia Manual Ortopédica',   [30=>['unit'=>45000]]);
        $crear($catComp, 'descontracturante_puncion',   'Descontracturante + Punción', [30=>['unit'=>45000]]);
        $crear($catComp, 'sport_recovery_presoterapia', 'Sport Recovery + Presoterapia',[30=>['unit'=>45000]]);
        $crear($catComp, 'reflexologia',                'Reflexología',                [45=>['unit'=>35000]]);
        $crear($catComp, 'alivio_dolor',                'Alivio del dolor',            [30=>['unit'=>25000]]);
    }
}
