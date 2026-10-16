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
