<?php

namespace App;

use Carbon\Carbon;
use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use RealRashid\SweetAlert\Facades\Alert;

class Reserva extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected function auditoriaContexto()
    {
        return $this;
    }

    /** Historial completo: acciones sobre la reserva y sus registros hijos (venta, pedidos, masajes...). */
    public function auditoriasHistorial()
    {
        return $this->morphMany(Auditoria::class, 'contexto')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    protected $auditoriaCampos = [
        'cliente_id'             => ['Cliente', 'App\Cliente@nombre_cliente'],
        'id_programa'            => ['Programa', 'App\Programa@nombre_programa'],
        'fecha_visita'           => ['Fecha de visita', 'fecha'],
        'cantidad_personas'      => 'Personas',
        'cantidad_masajes'       => 'Masajes',
        'cantidad_masajes_extra' => 'Masajes extra',
        'estado'                 => 'Estado',
        'observacion'            => 'Observación',
        'avisado_en_cocina'      => ['Avisado en cocina', 'bool'],
        'menu_recibido'          => ['Menú recibido', 'bool'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $programa = optional($this->programa)->nombre_programa;

        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' ' . $this->textoReservaAuditoria($this)
            . ($evento === Auditoria::EVENTO_CREO && $programa
                ? ' (' . $programa . ', ' . $this->cantidad_personas . ' personas)'
                : '');
    }

    /**
     * Estado legible de las colecciones que se reemplazan en bloque, para
     * AuditoriaService::sincronizarReserva(). Siempre consulta la BD.
     */
    public function resumenAuditoria($que)
    {
        switch ($que) {
            case 'ubicaciones':
                return $this->ubicacionesAsignadas()->with('ubicacion')->orderBy('id')->get()
                    ->map(function ($asignada) {
                        $nombre = optional($asignada->ubicacion)->nombre ?: '#' . $asignada->id_ubicacion;

                        return $asignada->cantidad_personas ? $nombre . ' (' . $asignada->cantidad_personas . ' personas)' : $nombre;
                    })
                    ->implode(', ');
            case 'wellness':
                return $this->wellness()->pluck('tipo')->implode(', ');
            case 'desayuno_once':
                return $this->desayunoOnce()->orderBy('tipo')->pluck('tipo')->implode(', ');
        }

        return '';
    }

    protected $dates = ['fecha_visita'];


    protected $fillable = [
        'cantidad_personas', 'fecha_visita', 'descripcion', 'cliente_id',
        'cantidad_masajes',
        'observacion',
        'id_programa',
        'user_id',
        'avisado_en_cocina',
        'cantidad_masajes_extra'
    ];

    //RELACIONES

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa');
    }

    public function reagendamientos()
    {
        return $this->hasMany(Reagendamiento::class, 'id_reserva');
    }
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function venta()
    {
        return $this->hasOne(Venta::class, 'id_reserva');
    }
    
    public function visitas()
    {
        return $this->hasMany(Visita::class, 'id_reserva');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'id_reserva');
    }

    public function desayunoOnce()
    {
        return $this->hasMany(ReservaDesayunoOnce::class, 'id_reserva');
    }

    public function wellness()
    {
        return $this->hasOne(ReservaWellness::class, 'id_reserva');
    }

    public function ubicacionesAsignadas()
    {
        return $this->hasMany(ReservaUbicacion::class, 'id_reserva');
    }

    public function masajes()
    {
        return $this->hasMany(Masaje::class, 'id_reserva')->orderBy('horario_masaje', 'asc');
    }

    public function visitasOrdenadas()
    {
        return $this->hasMany(Visita::class, 'id_reserva')->orderBy('horario_sauna', 'asc');
    }

    public function estadoRecepcion()
    {
        return $this->hasOne(EstadoRecepcion::class, 'reserva_id');
    }

    // ===== Varios programas en una reserva (15/10/2026) =====

    public function programasReserva()
    {
        return $this->hasMany(ReservaPrograma::class, 'id_reserva')->orderByDesc('principal')->orderBy('id');
    }

    /**
     * Programas de la reserva como lista [programa, personas, valor_unitario, principal].
     * Sin filas en reserva_programas: un solo programa con todas las personas.
     */
    public function detalleProgramas()
    {
        static $hayTabla = null;
        if ($hayTabla === null) {
            $hayTabla = \Illuminate\Support\Facades\Schema::hasTable('reserva_programas');
        }
        if (! $hayTabla) {
            $filas = collect();
        } elseif ($this->relationLoaded('programasReserva')) {
            $filas = $this->programasReserva;
        } else {
            $filas = $this->programasReserva()->with('programa.servicios')->get();
            $this->setRelation('programasReserva', $filas);
        }
        if ($filas->isEmpty()) {
            return collect([(object) [
                'programa'       => $this->programa,
                'id_programa'    => $this->id_programa,
                'personas'       => (int) $this->cantidad_personas,
                'valor_unitario' => null,
                'principal'      => true,
            ]]);
        }
        return $filas->map(function ($f) {
            return (object) [
                'programa'       => $f->programa,
                'id_programa'    => $f->id_programa,
                'personas'       => (int) $f->cantidad_personas,
                'valor_unitario' => $f->valor_unitario,
                'principal'      => (bool) $f->principal,
            ];
        });
    }

    public function getTieneVariosProgramasAttribute(): bool
    {
        return $this->detalleProgramas()->count() > 1;
    }

    /** "Wellness Day" o "2 Wellness Day + 1 Full Day". */
    public function getProgramasTextoAttribute(): string
    {
        $d = $this->detalleProgramas();
        if ($d->count() <= 1) {
            return (string) optional($this->programa)->nombre_programa;
        }
        return $d->map(function ($p) {
            return $p->personas . ' ' . optional($p->programa)->nombre_programa;
        })->implode(' + ');
    }

    /** Personas de los programas que incluyen masaje. */
    public function personasConMasaje(): int
    {
        return (int) $this->detalleProgramas()->sum(function ($p) {
            return ($p->programa && $p->programa->incluye_masajes) ? $p->personas : 0;
        });
    }

    /**
     * Personas que comen desayuno/once segun sus programas. Con un solo programa
     * (o si el desayuno/once se agrego aparte) son todas las personas de la reserva.
     */
    public function personasDesayunoOnce(): int
    {
        $d = $this->detalleProgramas();
        if ($d->count() <= 1) {
            return (int) $this->cantidad_personas;
        }
        $n = (int) $d->sum(function ($p) {
            $incluye = $p->programa && $p->programa->servicios->contains(function ($s) {
                $x = mb_strtolower($s->nombre_servicio);
                return strpos($x, 'desayuno') !== false || strpos($x, 'once') !== false;
            });
            return $incluye ? $p->personas : 0;
        });
        return $n > 0 ? $n : (int) $this->cantidad_personas;
    }

    /** Para la boleta: [['nombre','personas','valor','subtotal'], ...] o null si es un solo programa. */
    public function detalleProgramasBoleta(): ?array
    {
        $d = $this->detalleProgramas();
        if ($d->count() <= 1) {
            return null;
        }
        return $d->map(function ($p) {
            $valor = (int) ($p->valor_unitario ?? optional($p->programa)->valor_programa);
            return [
                'nombre'   => (string) optional($p->programa)->nombre_programa,
                'personas' => (int) $p->personas,
                'valor'    => $valor,
                'subtotal' => $valor * (int) $p->personas,
            ];
        })->values()->all();
    }

    /**
     * Reservas que incluyen el programa (como principal o como adicional). Para conteos
     * de informes con reservas de varios programas (15/10/2026).
     */
    public function scopeConPrograma($query, $programaId)
    {
        return $query->where(function ($w) use ($programaId) {
            $w->whereIn('reservas.id', function ($s) use ($programaId) {
                $s->select('id_reserva')->from('reserva_programas')->where('id_programa', $programaId);
            })->orWhere(function ($x) use ($programaId) {
                $x->where('reservas.id_programa', $programaId)
                    ->whereNotIn('reservas.id', function ($s) {
                        $s->select('id_reserva')->from('reserva_programas');
                    });
            });
        });
    }

    /**
     * Solo programas wellness (terraza/reposera) y sin estacion extra: el formulario de
     * ubicaciones pide la preferencia terraza o reposera. Con otros programas o con estacion
     * extra se muestran todas las ubicaciones permitidas. 15/10/2026
     */
    public function getSoloWellnessAttribute(): bool
    {
        foreach ($this->detalleProgramas() as $dp) {
            if (optional($dp->programa)->espacio_tipo !== 'wellness') {
                return false;
            }
        }
        return ! $this->tieneEstacionExtra();
    }

    /** ¿Se agrego una estacion de descanso como servicio extra? */
    public function tieneEstacionExtra(): bool
    {
        $consumo = optional($this->venta)->consumo;
        if (! $consumo) {
            return false;
        }
        return $consumo->detalleServiciosExtra()->with('servicio')->get()->contains(function ($d) {
            return $d->servicio && mb_stripos($d->servicio->nombre_servicio, 'estaci') !== false;
        });
    }

    /** Nombres de servicios de todos los programas de la reserva (sin repetir). */
    public function serviciosNombres(): array
    {
        return $this->detalleProgramas()->flatMap(function ($p) {
            return $p->programa ? $p->programa->servicios->pluck('nombre_servicio')->all() : [];
        })->unique()->values()->all();
    }
    //ALMACENAMIENTO

    public function store($request)
    {
        if ($request->has('cliente_id')) {
            $cliente = $request->cliente_id;
            $request->merge(['cliente_id' => $cliente]);
        }

        $user_id = auth()->id();

        $request->merge(['user_id' => $user_id]);

        Alert::success('Exito', 'Reserva Realizada', 'Confirmar')->showConfirmButton();
        return self::create($request->all());
    }

    //VALIDACION

    public function getIncluyeMasajesExtraAttribute() 
    {
        return $this->masajes()->exists();    
    }

    public function getIncluyeAlmuerzosExtraAttribute() 
    {
        return $this->menus()->exists();
    }


    //RECUPERACION DE INFORMACION
    public function getFechaVisitaAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }



    // public function getHoraFinAttribute()
    // {
    //     // Identifica el servicio específico, reemplaza $idServicio con el identificador correcto
    //     $servicioEspecifico = $this->programa->servicios->firstWhere('id_servicio', $servicio->id);

    //     if ($this->horario_sauna && $servicioEspecifico) {
    //         $duracion = $servicioEspecifico->duracion;
    //         $horaInicio = Carbon::parse($this->horario_sauna);
    //         $horaFin = $horaInicio->addMinutes($duracion);
    //         return $horaFin->format('H:i A');
    //     }

    //     return null;
    // }

    // public function HoraFin($inicio, $duracion)
    // {
    //     // Asegúrate de que haya un horario_inicio y una duración de servicio
    //     if ($inicio && $duracion) {
    //         // Convierte el horario_inicio en una instancia de Carbon
    //         $horaInicio = Carbon::parse($inicio);
            
    //         // Suma la duración del servicio en minutos
    //         $horaFin = $horaInicio->addMinutes($duracion);

    //         // Devuelve la hora fin en el formato deseado
    //         return $horaFin->format('H:i A');
    //     }

    //     return null;
    // }

//OTRAS OPERACIONES


    public function getHorarioMasajeAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }


    public function getHoraFinMasajeAttribute()
    {
        return $this->calcularHoraFin($this->horario_masaje, ['Masaje', 'Masajes']);
    }

    public function getHoraFinMasajeExtraAttribute()
    {
        return $this->calcularHoraFinMasajeExtra($this->horario_masaje);
    }



    private function calcularHoraFin($horarioInicio, $nombreServicio)
    {

        $servicio = $this->programa->servicios->first(function ($servicio) use ($nombreServicio) {
            return in_array($servicio->nombre_servicio, $nombreServicio);
        });
        if ($horarioInicio && $servicio) {
            $horaInicio = Carbon::parse($horarioInicio);
            return $horaInicio->addMinutes($servicio->duracion)->format('H:i');
        }

        return null;
    }

    private function calcularHoraFinMasajeExtra($horarioInicio)
    {
        $nombreServicio = ['Masaje', 'Masajes'];

        $servicio = Servicio::whereIn('nombre_servicio', $nombreServicio)->first();

        if ($horarioInicio && $servicio) {
            $horaInicio = Carbon::parse($horarioInicio);
            return $horaInicio->addMinutes($servicio->duracion)->format('H:i');
        }

        return null;
    }

    public function getNextPersonaAttribute()
    {
        return (int) ($this->masajes()->max('persona') ?? 0) + 1;
    }
}
