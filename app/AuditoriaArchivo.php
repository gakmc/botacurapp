<?php

namespace App;

/**
 * Registros de auditoría que superaron su retención "en caliente".
 * Conserva el id original y agrega la fecha de archivado.
 */
class AuditoriaArchivo extends Auditoria
{
    protected $table = 'auditorias_archivo';

    public $incrementing = false;

    protected $dates = ['created_at', 'archivado_at'];
}
