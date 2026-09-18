<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ClassifyUbicacionesInventarioReal extends Migration
{
    public function up()
    {
        DB::table('ubicaciones')
            ->whereIn('nombre', ['Quillay 0', 'Quillay 1', 'Quillay 2', 'Quillay 3', 'Quillay 4', 'Quillay 5'])
            ->update([
                'espacio_tipo' => 'wellness',
                'sub_tipo' => 'terraza',
                'capacidad_min' => 4,
                'capacidad_max' => 6,
                'tiene_terraza' => false,
                'activo' => true,
            ]);

        DB::table('ubicaciones')
            ->whereIn('nombre', ['Nispero 1', 'Nispero 2'])
            ->update([
                'espacio_tipo' => 'estacion',
                'sub_tipo' => 'estacion_grupal',
                'capacidad_min' => 4,
                'capacidad_max' => 5,
                'tiene_terraza' => true,
                'activo' => true,
            ]);

        DB::table('ubicaciones')
            ->whereIn('nombre', ['Estación 1', 'Estación 2', 'Estación 3', 'Estación 4', 'Estación 5', 'Estación 6', 'Estación 7'])
            ->update([
                'espacio_tipo' => 'estacion',
                'sub_tipo' => 'estacion_normal',
                'capacidad_min' => 1,
                'capacidad_max' => 3,
                'tiene_terraza' => false,
                'activo' => true,
            ]);

        DB::table('ubicaciones')
            ->whereIn('nombre', ['Reposera 1', 'Reposera 2', 'Reposera 3', 'Reposera 4'])
            ->update([
                'espacio_tipo' => 'wellness',
                'sub_tipo' => 'reposera',
                'capacidad_min' => 1,
                'capacidad_max' => 2,
                'tiene_terraza' => false,
                'activo' => true,
            ]);

        DB::table('ubicaciones')
            ->where('nombre', 'Terraza Nisperos')
            ->update([
                'espacio_tipo' => null,
                'sub_tipo' => null,
                'capacidad_min' => null,
                'capacidad_max' => null,
                'tiene_terraza' => false,
                'activo' => false,
            ]);
    }

    public function down()
    {
        DB::table('ubicaciones')
            ->whereIn('nombre', [
                'Quillay 0', 'Quillay 1', 'Quillay 2', 'Quillay 3', 'Quillay 4', 'Quillay 5',
                'Nispero 1', 'Nispero 2',
                'Estación 1', 'Estación 2', 'Estación 3', 'Estación 4', 'Estación 5', 'Estación 6', 'Estación 7',
                'Reposera 1', 'Reposera 2', 'Reposera 3', 'Reposera 4',
                'Terraza Nisperos',
            ])
            ->update([
                'espacio_tipo' => null,
                'sub_tipo' => null,
                'capacidad_min' => null,
                'capacidad_max' => null,
                'tiene_terraza' => false,
                'activo' => true,
            ]);
    }
}
