<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Foto adjunta a un ticket: del reporte (origen) o de la solución (destino). */
class TicketImagen extends Model
{
    use Auditable;

    const ETAPA_REPORTE  = 'reporte';
    const ETAPA_SOLUCION = 'solucion';

    // AUDITORÍA (solo que se subió o quitó una foto, nunca la imagen)

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    // La frase ya dice si es del reporte o de la solución
    protected $auditoriaCampos = [];

    protected function auditoriaDescripcion($evento)
    {
        $foto = $this->etapa === self::ETAPA_SOLUCION ? 'una foto de la solución' : 'una foto';

        return $this->segunEvento($evento, 'agregó ' . $foto . ' al', 'editó ' . $foto . ' del', 'quitó ' . $foto . ' del')
            . ' ticket ' . optional($this->ticket)->codigo;
    }

    protected function auditoriaContexto()
    {
        return $this->ticket;
    }

    // MODELO

    protected $table = 'ticket_imagenes';

    protected $fillable = [
        'ticket_id', 'ruta', 'etapa', 'subida_por',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id')->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'subida_por');
    }
}
