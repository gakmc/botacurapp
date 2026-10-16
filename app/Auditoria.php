<?php

namespace App;

use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Registro de auditoría. Inmutable: una vez creado no se edita ni se elimina
 * desde el modelo. El archivado y la purga operan con el query builder.
 */
class Auditoria extends Model
{
    const UPDATED_AT = null;

    // Categorías (definen la retención, ver config/auditoria.php)
    const CATEGORIA_FINANCIERA  = 'financiera';
    const CATEGORIA_SEGURIDAD   = 'seguridad';
    const CATEGORIA_OPERACIONAL = 'operacional';

    // Tipos de actor
    const ACTOR_USUARIO  = 'usuario';
    const ACTOR_SISTEMA  = 'sistema';
    const ACTOR_CONSOLA  = 'consola';
    const ACTOR_API      = 'api';
    const ACTOR_BOT      = 'bot';
    const ACTOR_INVITADO = 'invitado';

    // Eventos
    const EVENTO_CREO          = 'creo';
    const EVENTO_EDITO         = 'edito';
    const EVENTO_ELIMINO       = 'elimino';
    const EVENTO_RESTAURO      = 'restauro';
    const EVENTO_ASIGNO        = 'asigno';
    const EVENTO_QUITO         = 'quito';
    const EVENTO_LOGIN         = 'login';
    const EVENTO_LOGOUT        = 'logout';
    const EVENTO_LOGIN_FALLIDO = 'login_fallido';
    const EVENTO_ACCION        = 'accion';
    const EVENTO_CERRO         = 'cerro';
    const EVENTO_APROBO        = 'aprobo';
    const EVENTO_RECHAZO       = 'rechazo';
    const EVENTO_EJECUTO       = 'ejecuto';

    const ETIQUETAS_EVENTO = [
        self::EVENTO_CREO          => 'Creó',
        self::EVENTO_EDITO         => 'Editó',
        self::EVENTO_ELIMINO       => 'Eliminó',
        self::EVENTO_RESTAURO      => 'Restauró',
        self::EVENTO_ASIGNO        => 'Asignó',
        self::EVENTO_QUITO         => 'Quitó',
        self::EVENTO_LOGIN         => 'Inicio de sesión',
        self::EVENTO_LOGOUT        => 'Cierre de sesión',
        self::EVENTO_LOGIN_FALLIDO => 'Inicio de sesión fallido',
        self::EVENTO_ACCION        => 'Acción',
        self::EVENTO_CERRO         => 'Cerró',
        self::EVENTO_APROBO        => 'Aprobó',
        self::EVENTO_RECHAZO       => 'Rechazó',
        self::EVENTO_EJECUTO       => 'Ejecutó',
    ];

    /** Columnas comunes a `auditorias` y `auditorias_archivo` (archivado y consultas combinadas). */
    const COLUMNAS_REGISTRO = [
        'id', 'user_id', 'user_nombre', 'user_roles', 'actor_tipo', 'actor_detalle',
        'categoria', 'evento', 'auditable_type', 'auditable_id', 'contexto_type', 'contexto_id',
        'descripcion', 'cambios', 'ip', 'ruta_nombre', 'request_id', 'created_at',
    ];

    protected $table = 'auditorias';

    protected $fillable = [
        'user_id', 'user_nombre', 'user_roles', 'actor_tipo', 'actor_detalle',
        'categoria', 'evento', 'auditable_type', 'auditable_id', 'contexto_type', 'contexto_id', 'descripcion', 'cambios',
        'ip', 'ruta_nombre', 'request_id',
    ];

    protected $casts = [
        'cambios' => 'array',
    ];

    protected $dates = ['created_at'];

    protected static function boot()
    {
        parent::boot();

        static::updating(function () {
            throw new LogicException('Los registros de auditoría no se pueden modificar.');
        });

        static::deleting(function () {
            throw new LogicException('Los registros de auditoría no se pueden eliminar.');
        });
    }

    // RELACIONES

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /** Registro padre que agrupa la acción (ej. la Reserva). */
    public function contexto()
    {
        return $this->morphTo();
    }

    // RECUPERACION DE INFORMACIÓN

    /**
     * Etiqueta legible del evento. Ej: 'creo' => 'Creó'.
     * Eventos sin etiqueta definida (ej. 'cerro') se muestran capitalizados.
     */
    public function getEventoEtiquetaAttribute()
    {
        return self::etiquetaEvento($this->evento);
    }

    public static function etiquetaEvento($evento)
    {
        return isset(self::ETIQUETAS_EVENTO[$evento])
            ? self::ETIQUETAS_EVENTO[$evento]
            : ucfirst(str_replace('_', ' ', $evento));
    }

    /**
     * Quién realizó la acción, listo para mostrar.
     * Ej: "Gabriel (administrador)" o "Sistema (cerrar:sueldos)".
     */
    public function getActorAttribute()
    {
        if ($this->user_nombre) {
            return $this->user_roles
                ? $this->user_nombre . ' (' . $this->user_roles . ')'
                : $this->user_nombre;
        }

        $nombres = [
            self::ACTOR_SISTEMA  => 'Sistema',
            self::ACTOR_CONSOLA  => 'Consola',
            self::ACTOR_API      => 'Sistema',
            self::ACTOR_BOT      => 'Bot',
            self::ACTOR_INVITADO => 'Visitante no identificado',
        ];
        $nombre = isset($nombres[$this->actor_tipo]) ? $nombres[$this->actor_tipo] : ucfirst($this->actor_tipo);

        return $this->actor_detalle ? $nombre . ' (' . $this->actor_detalle . ')' : $nombre;
    }

    /**
     * Frase completa. Ej: "Gabriel (administrador) creó la reserva #512
     * para el cliente Juan Pérez el 01-10-2026 a las 09:00".
     */
    public function getFraseAttribute()
    {
        $cuando = $this->created_at
            ? ' el ' . app(AuditoriaService::class)->fechaHora($this->created_at)
            : '';

        return $this->actor . ' ' . $this->descripcion . $cuando;
    }
}
