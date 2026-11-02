<?php

namespace App;

use App\Concerns\Auditable;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Ticket (tarea pendiente) de un departamento (rol) a otro.
 *
 *   Pendiente → Recepcionado → En revisión → Solucionado → En verificación → Finalizado
 *   (destino trabaja)                        (origen revisa; puede reabrir → En revisión)
 *   Además: Rechazado (el destino dice que no le corresponde) y Anulado.
 *
 * Este modelo define qué acción procede desde cada estado y qué lado la hace
 * (origen, destino o sistema). Quién del lado puede hacerla lo decide la policy.
 */
class Ticket extends Model
{
    use SoftDeletes;
    use Auditable {
        formatearValorAuditoria as protected formatearValorAuditoriaBase;
        registrarAuditoria as protected registrarAuditoriaBase;
    }

    // ESTADOS

    const ESTADO_PENDIENTE       = 'pendiente';
    const ESTADO_RECEPCIONADO    = 'recepcionado';
    const ESTADO_EN_REVISION     = 'en_revision';
    const ESTADO_SOLUCIONADO     = 'solucionado';
    const ESTADO_EN_VERIFICACION = 'en_verificacion';
    const ESTADO_FINALIZADO      = 'finalizado';
    const ESTADO_RECHAZADO       = 'rechazado';
    const ESTADO_ANULADO         = 'anulado';

    const ESTADOS = [
        self::ESTADO_PENDIENTE       => 'Pendiente',
        self::ESTADO_RECEPCIONADO    => 'Recepcionado',
        self::ESTADO_EN_REVISION     => 'En revisión',
        self::ESTADO_SOLUCIONADO     => 'Solucionado',
        self::ESTADO_EN_VERIFICACION => 'En verificación',
        self::ESTADO_FINALIZADO      => 'Finalizado',
        self::ESTADO_RECHAZADO       => 'Rechazado',
        self::ESTADO_ANULADO         => 'Anulado',
    ];

    /** Salen de la bandeja principal y quedan en el historial. */
    const ESTADOS_CERRADOS = [self::ESTADO_FINALIZADO, self::ESTADO_ANULADO];

    /** Esperan la confirmación del origen; se finalizan solos tras config('app.tickets_dias_autocierre'). */
    const ESTADOS_POR_CONFIRMAR = [self::ESTADO_SOLUCIONADO, self::ESTADO_EN_VERIFICACION];

    /** Estados que esperan una acción de cada lado (contadores de la bandeja y del menú). */
    const ESPERAN_AL_DESTINO = [self::ESTADO_PENDIENTE, self::ESTADO_RECEPCIONADO, self::ESTADO_EN_REVISION];
    const ESPERAN_AL_ORIGEN  = [self::ESTADO_SOLUCIONADO, self::ESTADO_EN_VERIFICACION, self::ESTADO_RECHAZADO];

    /** Columna de fecha que se marca al llegar a cada estado. */
    const FECHA_POR_ESTADO = [
        self::ESTADO_RECEPCIONADO    => 'recepcionado_at',
        self::ESTADO_SOLUCIONADO     => 'solucionado_at',
        self::ESTADO_EN_VERIFICACION => 'verificacion_at',
        self::ESTADO_FINALIZADO      => 'finalizado_at',
        self::ESTADO_ANULADO         => 'anulado_at',
    ];

    // PRIORIDADES (en orden de urgencia para la bandeja)

    const PRIORIDADES = [
        'urgente' => 'Urgente',
        'alta'    => 'Alta',
        'normal'  => 'Normal',
        'baja'    => 'Baja',
    ];

    // ACCIONES

    const LADO_ORIGEN  = 'origen';
    const LADO_DESTINO = 'destino';
    const LADO_SISTEMA = 'sistema';

    /**
     * accion => desde (estados), hacia (estado), lado que la ejecuta,
     * comentario obligatorio y texto del botón.
     * El administrador además puede 'anular' cualquier ticket (policy).
     */
    const ACCIONES = [
        'recepcionar' => [
            'desde' => [self::ESTADO_PENDIENTE],
            'hacia' => self::ESTADO_RECEPCIONADO, 'lado' => self::LADO_DESTINO, 'comentario' => false, 'boton' => 'Lo tomo',
        ],
        'comenzar' => [
            'desde' => [self::ESTADO_RECEPCIONADO],
            'hacia' => self::ESTADO_EN_REVISION, 'lado' => self::LADO_DESTINO, 'comentario' => false, 'boton' => 'Comenzar',
        ],
        'solucionar' => [
            'desde' => [self::ESTADO_RECEPCIONADO, self::ESTADO_EN_REVISION],
            'hacia' => self::ESTADO_SOLUCIONADO, 'lado' => self::LADO_DESTINO, 'comentario' => false, 'boton' => 'Marcar solucionado',
        ],
        'rechazar' => [
            'desde' => [self::ESTADO_PENDIENTE, self::ESTADO_RECEPCIONADO, self::ESTADO_EN_REVISION],
            'hacia' => self::ESTADO_RECHAZADO, 'lado' => self::LADO_DESTINO, 'comentario' => true, 'boton' => 'No corresponde',
        ],
        'revisar' => [
            'desde' => [self::ESTADO_SOLUCIONADO],
            'hacia' => self::ESTADO_EN_VERIFICACION, 'lado' => self::LADO_ORIGEN, 'comentario' => false, 'boton' => 'Revisar',
        ],
        'finalizar' => [
            'desde' => self::ESTADOS_POR_CONFIRMAR,
            'hacia' => self::ESTADO_FINALIZADO, 'lado' => self::LADO_ORIGEN, 'comentario' => false, 'boton' => 'Finalizar',
        ],
        'reabrir' => [
            'desde' => self::ESTADOS_POR_CONFIRMAR,
            'hacia' => self::ESTADO_EN_REVISION, 'lado' => self::LADO_ORIGEN, 'comentario' => true, 'boton' => 'Reabrir',
        ],
        'reenviar' => [
            'desde' => [self::ESTADO_RECHAZADO],
            'hacia' => self::ESTADO_PENDIENTE, 'lado' => self::LADO_ORIGEN, 'comentario' => false, 'boton' => 'Reenviar a otro departamento',
        ],
        'anular' => [
            'desde' => [
                self::ESTADO_PENDIENTE, self::ESTADO_RECEPCIONADO, self::ESTADO_EN_REVISION,
                self::ESTADO_SOLUCIONADO, self::ESTADO_EN_VERIFICACION, self::ESTADO_RECHAZADO,
            ],
            'hacia' => self::ESTADO_ANULADO, 'lado' => self::LADO_ORIGEN, 'comentario' => false, 'boton' => 'Anular',
        ],
        'autocerrar' => [
            'desde' => self::ESTADOS_POR_CONFIRMAR,
            'hacia' => self::ESTADO_FINALIZADO, 'lado' => self::LADO_SISTEMA, 'comentario' => false, 'boton' => null,
        ],
    ];

    /** Acciones que van como botón principal en la tarjeta, en orden de preferencia. */
    const ACCIONES_PRINCIPALES = ['recepcionar', 'comenzar', 'solucionar', 'revisar', 'finalizar'];

    /** Ícono de Material Icons por acción. */
    const ICONOS_ACCION = [
        'recepcionar' => 'pan_tool', 'comenzar' => 'play_arrow', 'solucionar' => 'check',
        'revisar' => 'visibility', 'finalizar' => 'done_all', 'reabrir' => 'replay',
        'rechazar' => 'block', 'reenviar' => 'forward', 'anular' => 'delete',
    ];

    /** Colores de Materialize para el estado (badge de la tarjeta). */
    const COLORES_ESTADO = [
        self::ESTADO_PENDIENTE       => 'grey',
        self::ESTADO_RECEPCIONADO    => 'blue',
        self::ESTADO_EN_REVISION     => 'amber darken-2',
        self::ESTADO_SOLUCIONADO     => 'green',
        self::ESTADO_EN_VERIFICACION => 'teal',
        self::ESTADO_FINALIZADO      => 'green darken-3',
        self::ESTADO_RECHAZADO       => 'red',
        self::ESTADO_ANULADO         => 'grey darken-2',
    ];

    const COLORES_PRIORIDAD = [
        'urgente' => 'red', 'alta' => 'orange darken-2', 'normal' => 'cyan darken-2', 'baja' => 'blue-grey lighten-1',
    ];

    // AUDITORÍA

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaCampos = [
        'titulo'            => 'Título',
        'estado'            => ['Estado', 'estado_ticket'],
        'prioridad'         => ['Prioridad', 'prioridad_ticket'],
        'rol_origen_id'     => ['Departamento origen', 'App\Role@name'],
        'rol_destino_id'    => ['Departamento destino', 'App\Role@name'],
        'responsable_id'    => ['Responsable', 'App\User@name'],
        'ubicacion'         => 'Ubicación',
    ];

    protected function auditoriaDescripcion($evento)
    {
        if ($evento === Auditoria::EVENTO_CREO) {
            return 'creó el ticket ' . $this->codigo . ' de ' . optional($this->rolOrigen)->name
                . ' para ' . optional($this->rolDestino)->name;
        }

        if ($evento === Auditoria::EVENTO_EDITO && $this->isDirty('rol_destino_id')) {
            return 'reenvió el ticket ' . $this->codigo . ' a ' . optional(Role::find($this->rol_destino_id))->name;
        }

        // "Tomarlo yo": otro miembro del destino pasa a ser responsable sin cambiar el estado
        if ($evento === Auditoria::EVENTO_EDITO && $this->isDirty('responsable_id') && ! $this->isDirty('estado')) {
            $anterior = optional(User::find($this->getOriginal('responsable_id')))->name;

            return 'tomó el ticket ' . $this->codigo . ($anterior ? ' (antes a cargo de ' . $anterior . ')' : '');
        }

        if ($evento === Auditoria::EVENTO_EDITO && $this->isDirty('estado')) {
            if ($this->estado === self::ESTADO_FINALIZADO && $this->finalizado_automatico) {
                return 'finalizó automáticamente el ticket ' . $this->codigo . ' por falta de confirmación';
            }

            return 'cambió el ticket ' . $this->codigo . ' de ' . self::etiquetaEstado($this->getOriginal('estado'))
                . ' a ' . self::etiquetaEstado($this->estado);
        }

        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' el ticket ' . $this->codigo;
    }

    /**
     * Las ediciones que corresponden a un paso del flujo se registran con su propio
     * evento, para poder filtrarlas en la auditoría (Asignó, Cerró, Rechazó, Anuló).
     */
    public function registrarAuditoria($evento)
    {
        $especifico = $evento === Auditoria::EVENTO_EDITO ? $this->eventoAuditoriaEdicion() : $evento;

        if ($especifico === $evento) {
            $this->registrarAuditoriaBase($evento);
            return;
        }

        // Sigue siendo una edición: cambios (antes → después) y frase de edición; solo cambia la etiqueta
        $servicio = app(AuditoriaService::class);
        if ($servicio->estaOmitido()) {
            $servicio->contarSuprimido($this, $especifico);
            return;
        }

        $cambios = $this->cambiosAuditables(Auditoria::EVENTO_EDITO);
        if (empty($cambios)) {
            return;
        }

        $servicio->registrar([
            'categoria'   => $this->auditoriaCategoria,
            'evento'      => $especifico,
            'auditable'   => $this,
            'contexto'    => $this->contextoAuditoriaSeguro(),
            'descripcion' => $this->descripcionAuditoriaSegura(Auditoria::EVENTO_EDITO),
            'cambios'     => $cambios,
        ]);
    }

    protected function eventoAuditoriaEdicion()
    {
        // Tomar o reenviar: cambia quién (o qué departamento) se hace cargo
        if ($this->isDirty('rol_destino_id') || ($this->isDirty('responsable_id') && $this->responsable_id)) {
            return Auditoria::EVENTO_ASIGNO;
        }

        if ($this->isDirty('estado')) {
            switch ($this->estado) {
                case self::ESTADO_FINALIZADO: return Auditoria::EVENTO_CERRO;
                case self::ESTADO_RECHAZADO:  return Auditoria::EVENTO_RECHAZO;
                case self::ESTADO_ANULADO:    return Auditoria::EVENTO_ANULO;
            }
        }

        return Auditoria::EVENTO_EDITO;
    }

    /** Nombre del ticket en la pantalla de auditoría: Ticket "TK-000123 · Fuga en lavaplatos". */
    public function tituloAuditoria()
    {
        return $this->codigo . ' · ' . $this->titulo;
    }

    protected function formatearValorAuditoria($valor, $formato)
    {
        if ($formato === 'estado_ticket') {
            return $valor === null || $valor === '' ? null : self::etiquetaEstado($valor);
        }
        if ($formato === 'prioridad_ticket') {
            return $valor === null || $valor === '' ? null : (self::PRIORIDADES[$valor] ?? $valor);
        }

        return $this->formatearValorAuditoriaBase($valor, $formato);
    }

    // MODELO

    protected $table = 'tickets';

    protected $fillable = [
        'titulo', 'descripcion', 'prioridad', 'estado', 'rol_origen_id', 'rol_destino_id',
        'creado_por', 'responsable_id', 'ubicacion',
    ];

    protected $casts = [
        'finalizado_automatico' => 'boolean',
    ];

    protected $dates = [
        'recepcionado_at', 'solucionado_at', 'verificacion_at', 'finalizado_at', 'anulado_at',
        'estado_cambiado_at', 'recordatorio_enviado_at', 'deleted_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            $ticket->estado = $ticket->estado ?: self::ESTADO_PENDIENTE;
            $ticket->prioridad = $ticket->prioridad ?: 'normal';
            $ticket->estado_cambiado_at = $ticket->estado_cambiado_at ?: Carbon::now();
        });
    }

    // RELACIONES

    public function rolOrigen()
    {
        return $this->belongsTo(Role::class, 'rol_origen_id');
    }

    public function rolDestino()
    {
        return $this->belongsTo(Role::class, 'rol_destino_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function imagenes()
    {
        return $this->hasMany(TicketImagen::class, 'ticket_id');
    }

    public function eventos()
    {
        return $this->hasMany(TicketEvento::class, 'ticket_id')->orderBy('created_at')->orderBy('id');
    }

    // SCOPES

    public function scopeActivos($query)
    {
        return $query->whereNotIn('estado', self::ESTADOS_CERRADOS);
    }

    public function scopeCerrados($query)
    {
        return $query->whereIn('estado', self::ESTADOS_CERRADOS);
    }

    /** Tickets que envió o recibe alguno de los roles del usuario. */
    public function scopeVisiblesPara($query, User $user)
    {
        $roles = self::rolIds($user);

        return $query->where(function ($q) use ($roles) {
            $q->whereIn('rol_destino_id', $roles)->orWhereIn('rol_origen_id', $roles);
        });
    }

    public function scopeRecibidosPor($query, User $user)
    {
        return $query->whereIn('rol_destino_id', self::rolIds($user));
    }

    public function scopeEnviadosPor($query, User $user)
    {
        return $query->whereIn('rol_origen_id', self::rolIds($user));
    }

    /** Urgentes primero y luego los más antiguos. */
    public function scopeOrdenBandeja($query)
    {
        $prioridades = "'" . implode("','", array_keys(self::PRIORIDADES)) . "'";

        return $query->orderByRaw("FIELD(prioridad, $prioridades)")->orderBy('created_at');
    }

    /** Solucionados / en verificación sin movimiento desde hace $dias. */
    public function scopeSinConfirmarDesde($query, $dias)
    {
        return $query->whereIn('estado', self::ESTADOS_POR_CONFIRMAR)
            ->where('estado_cambiado_at', '<=', Carbon::now()->subDays($dias));
    }

    /** ['recibidos' => n, 'enviados' => n]: lo que espera una acción del usuario en cada pestaña. */
    public static function contadoresPara(User $user)
    {
        return [
            'recibidos' => self::recibidosPor($user)->whereIn('estado', self::ESPERAN_AL_DESTINO)->count(),
            'enviados'  => self::enviadosPor($user)->whereIn('estado', self::ESPERAN_AL_ORIGEN)->count(),
        ];
    }

    // REGLAS

    public static function rolIds(User $user)
    {
        return $user->roles->pluck('id')->all();
    }

    public function esDelOrigen(User $user)
    {
        return in_array($this->rol_origen_id, self::rolIds($user));
    }

    public function esDelDestino(User $user)
    {
        return in_array($this->rol_destino_id, self::rolIds($user));
    }

    public function estaCerrado()
    {
        return in_array($this->estado, self::ESTADOS_CERRADOS);
    }

    /** La acción procede desde el estado actual (sin mirar quién la hace). */
    public function admiteAccion($accion)
    {
        return isset(self::ACCIONES[$accion]) && in_array($this->estado, self::ACCIONES[$accion]['desde']);
    }

    /** Acciones que proceden desde el estado actual para un lado (origen | destino | sistema). */
    public function accionesDelLado($lado)
    {
        return array_keys(array_filter(self::ACCIONES, function ($def) use ($lado) {
            return $def['lado'] === $lado && in_array($this->estado, $def['desde']);
        }));
    }

    /**
     * Deja el ticket en el estado de destino de la acción y marca sus fechas.
     * No guarda: lo hace TicketService junto con el evento de la línea de tiempo.
     */
    public function aplicarAccion($accion)
    {
        if (! $this->admiteAccion($accion)) {
            throw new \DomainException('El ticket ' . $this->codigo . ' está ' . $this->estado_etiqueta
                . ' y no admite la acción "' . $accion . '".');
        }

        $ahora = Carbon::now();
        $this->estado = self::ACCIONES[$accion]['hacia'];
        $this->estado_cambiado_at = $ahora;

        if (isset(self::FECHA_POR_ESTADO[$this->estado])) {
            $this->{self::FECHA_POR_ESTADO[$this->estado]} = $ahora;
        }

        if ($accion === 'autocerrar') {
            $this->finalizado_automatico = true;
        }

        // Vuelve a la bandeja del nuevo destino sin responsable
        if ($accion === 'reenviar') {
            $this->responsable_id = null;
            $this->recepcionado_at = null;
        }

        return $this;
    }

    /**
     * Lugares para sugerir al escribir la ubicación: las ubicaciones activas del
     * local y los lugares ya usados en tickets (los más frecuentes primero),
     * sin repetir aunque difieran en mayúsculas o tildes.
     */
    public static function sugerenciasUbicacion($limite = 300)
    {
        $usados = self::withTrashed()->whereNotNull('ubicacion')
            ->select('ubicacion')->groupBy('ubicacion')
            ->orderByRaw('COUNT(*) DESC')->limit($limite)->pluck('ubicacion');

        $local = Ubicacion::where('activo', true)->orderBy('nombre')->pluck('nombre');

        return $local->merge($usados)
            ->map(function ($lugar) { return trim($lugar); })
            ->filter()
            ->unique(function ($lugar) { return Str::lower(Str::ascii($lugar)); })
            ->values()
            ->all();
    }

    // ATRIBUTOS

    public function getCodigoAttribute()
    {
        return 'TK-' . str_pad($this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function getEstadoEtiquetaAttribute()
    {
        return self::etiquetaEstado($this->estado);
    }

    public function getPrioridadEtiquetaAttribute()
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    /** Cuándo se finalizará solo si el origen no confirma (null si no aplica). */
    public function getFechaAutocierreAttribute()
    {
        if (! in_array($this->estado, self::ESTADOS_POR_CONFIRMAR) || ! $this->estado_cambiado_at) {
            return null;
        }

        return $this->estado_cambiado_at->copy()->addDays((int) config('app.tickets_dias_autocierre'));
    }

    public static function etiquetaEstado($estado)
    {
        return self::ESTADOS[$estado] ?? $estado;
    }
}
