<?php
namespace App\Http\Controllers;

use App\CategoriaMasaje;
use App\Consumo;
use App\DetalleServiciosExtra;
use App\Http\Requests\Visita\StoreRequest;
use App\Http\Requests\Visita\UpdateRequest;
use App\LugarMasaje;
use App\Mail\RegistroReservaMailable;
use App\Masaje;
use App\Menu;
use App\PrecioTipoMasaje;
use App\Producto;
use App\Reserva;
use App\Servicio;
use App\TipoMasaje;
use App\Ubicacion;
use App\Visita;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert;

class VisitaController extends Controller
{
    public function index()
    {
        // Asignacion de dias Hoy y Mañana
        $hoy    = Carbon::today();
        $manana = Carbon::tomorrow();

        // Filtrar las reservas que tienen visitas y cuya fecha de visita es hoy o mañana
        $reservas = Reserva::with('visitas', 'cliente', 'programa', 'user')
            ->whereBetween('fecha_visita', [$hoy, $manana])
            ->get();

        // Filtrar por visitas de Hoy
        $reservasHoy = $reservas->filter(function ($reserva) use ($hoy) {
            return Carbon::parse($reserva->fecha_visita)->isSameDay($hoy);
        });

        // Filtrar por visitas de Mañana
        $reservasManana = $reservas->filter(function ($reserva) use ($manana) {
            return Carbon::parse($reserva->fecha_visita)->isSameDay($manana);
        });

        //Retorno de la vista
        return view('themes.backoffice.pages.visita.index', [
            'reservasHoy'    => $reservasHoy,
            'reservasManana' => $reservasManana,
            //Reservas para la relacion con visitas
            // 'reservas' => Reserva::with('cliente', 'programa', 'user')->get(),
        ]);
    }

    public function create($reserva)
    {
        // session()->put('masajesExtra',true);
        // session()->put('cantidadMasajesExtra',3);
        // dd(session()->get('cantidadMasajesExtra'));

        // $masajesExtra         = session()->get('masajesExtra');
        // $almuerzosExtra       = session()->get('almuerzosExtra');
        // $cantidadMasajesExtra = session()->get('cantidadMasajesExtra');

        // $reserva              = Reserva::with(['venta'])->findOrFail($reserva);
        $reserva = Reserva::with(['venta', 'programa.servicios'])->findOrFail($reserva);

        $almuerzosExtra = false;

        $ventaId = optional($reserva->venta)->id;
        if ($ventaId) {
            $consumoId = Consumo::where('id_venta', $ventaId)->value('id');
            if ($consumoId) {
                $idServicioAlmuerzo = Servicio::whereIn('nombre_servicio', ['Almuerzo', 'Almuerzos', 'almuerzo', 'almuerzos'])->value('id');
                if ($idServicioAlmuerzo) {
                    $almuerzosExtra = DetalleServiciosExtra::where('id_consumo', $consumoId)
                        ->where('id_servicio_extra', $idServicioAlmuerzo)
                        ->exists();
                }
            }
        }

        $masajesExtra         = (int) ($reserva->cantidad_masajes_extra ?? 0) > 0;
        $cantidadMasajesExtra = (int) ($reserva->cantidad_masajes_extra ?? 0);

        // Incluye masaje en el programa (acepta "Masaje" o "Masajes")
        $incluyeMasajePrograma = $reserva->programa
        && $reserva->programa->servicios
        && $reserva->programa->servicios->contains(function ($s) {
            $nombre = mb_strtolower(trim($s->nombre_servicio));
            return in_array($nombre, ['masaje', 'masajes']);
        });

                            // Regla: nunca ambos a la vez
        $modoMasaje = null; // 'extra' | 'programa' | null

        if ($masajesExtra) {
            $modoMasaje = 'extra';
        } elseif ($incluyeMasajePrograma) {
            $modoMasaje = 'programa';
        }

        // cantidad de slots según modo
        $cantidadSlotsMasaje = 0;

        if ($modoMasaje === 'extra') {
            // 1 masaje = 1 slot
            $cantidadSlotsMasaje = (int) $cantidadMasajesExtra;
        } elseif ($modoMasaje === 'programa') {
            // 2 personas por slot (último puede ser 1)
            $cantidadSlotsMasaje = (int) ceil(((int) $reserva->cantidad_personas) / 2);
        }

        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();

        // Obtenemos la fecha seleccionada del formulario
        // $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
        $fechaSeleccionada = $reserva->fecha_visita;

        $ubicacionesOcupadas = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->join('ubicaciones', 'visitas.id_ubicacion', '=', 'ubicaciones.id')
            ->where('reservas.fecha_visita', \Carbon\Carbon::createFromFormat('d-m-Y', $fechaSeleccionada)->format('Y-m-d'))
            ->pluck('ubicaciones.nombre')
            ->map(function ($nombre) {
                return $nombre;
            })
            ->toArray();

        $ubicacionesAll = DB::table('ubicaciones')
            ->select('id', 'nombre')
            ->get();

        $ubicaciones = $ubicacionesAll->filter(function ($ubicacion) use ($ubicacionesOcupadas) {
            return ! in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();

        // ===============================HORAS=SPA==============================================
        // Horarios disponibles de 10:00 a 18:30 SPA
        $horaInicio = new \DateTime('10:00');
        $horaFin    = new \DateTime('18:30');
        $intervalo  = new \DateInterval('PT30M');
        $horarios   = [];

        while ($horaInicio <= $horaFin) {
            $horarios[] = $horaInicio->format('H:i');
            $horaInicio->add($intervalo);
        }

        // Obtener horarios ocupados de la tabla 'visitas'
        $horariosOcupados = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->where('reservas.fecha_visita', \Carbon\Carbon::createFromFormat('d-m-Y', $fechaSeleccionada)->format('Y-m-d'))
            ->pluck('visitas.horario_sauna')
            ->filter(function ($hora) {
                // Filtrar valores nulos o vacíos
                return ! is_null($hora) && $hora !== '';
            })
            ->map(function ($hora) {
                // Formatear solo los horarios válidos
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

        //=================================HORAS=MASAJES=========================================

        // Horarios disponibles de 10:20 a 19:00 con intervalos de 10 minutos entre sesiones de masaje
        $horaInicioMasajes = new \DateTime('10:20');
        $horaFinMasajes    = new \DateTime('18:30');
        $duracionMasaje    = new \DateInterval('PT30M'); // 30 minutos de duración
        $intervalos        = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
        $horarios          = [];

        while ($horaInicioMasajes <= $horaFinMasajes) {
            $horarios[] = $horaInicioMasajes->format('H:i');
            $horaInicioMasajes->add($duracionMasaje);
            $horaInicioMasajes->add($intervalos);
        }

        // Obtener las horas de inicio ocupadas de la tabla 'visitas' para masajes
        $horariosOcupadosMasajes = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->join('masajes as m', 'm.id_reserva', '=', 'reservas.id')
            ->where('reservas.fecha_visita', \Carbon\Carbon::createFromFormat('d-m-Y', $fechaSeleccionada)->format('Y-m-d'))
            ->whereNotNull('m.horario_masaje')
            ->select('m.horario_masaje', 'm.id_lugar_masaje')
            ->get()
            ->groupBy('id_lugar_masaje');

        // Procesar horarios ocupados
        $ocupadosPorLugar = [
            1 => [], // Containers
            2 => [], // Toldos
        ];

        foreach ($horariosOcupadosMasajes as $lugar => $visitas) {
            $ocupadosPorLugar[$lugar] = $visitas->pluck('horario_masaje')
                ->map(function ($hora) {
                    return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
                })
                ->toArray();
        }

        // Filtrar horarios disponibles por lugar
        $horariosDisponiblesMasajes = [
            1 => array_values(array_diff($horarios, $ocupadosPorLugar[1])), // Containers
            2 => array_values(array_diff($horarios, $ocupadosPorLugar[2])), // Toldos
        ];

        // // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        // $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // Obtener productos de tipo "entrada"
        $entradas = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        $catalogoMasajes = collect();

        // Catálogo: Categoría -> Tipos (activos) -> Precios
        if ($modoMasaje === 'extra') {
            $catalogoMasajes = CategoriaMasaje::query()
                ->select('id', 'nombre', 'slug')
                ->with(['tipos' => function ($q) {
                    $q->select('id', 'id_categoria_masaje', 'nombre', 'slug', 'activo')
                        ->where('activo', 1)
                        ->orderBy('nombre', 'asc')
                        ->with(['precios' => function ($p) {
                            $p->select('id', 'id_tipo_masaje', 'duracion_minutos', 'precio_unitario', 'precio_pareja')
                                ->orderBy('duracion_minutos', 'asc');
                        }]);
                }])
                ->orderBy('nombre', 'asc')
                ->get()
                ->map(function ($cat) {
                    return [
                        'id'     => $cat->id,
                        'nombre' => $cat->nombre,
                        'slug'   => $cat->slug ?: Str::slug($cat->nombre),
                        'tipos'  => $cat->tipos->map(function ($t) {
                            return [
                                'id'      => $t->id,
                                'nombre'  => $t->nombre,
                                'slug'    => $t->slug ?: Str::slug($t->nombre),
                                'precios' => $t->precios->map(function ($p) {
                                    return [
                                        'id'               => $p->id,
                                        'duracion_minutos' => (int) $p->duracion_minutos,
                                        'precio_unitario'  => (int) $p->precio_unitario,
                                        'precio_pareja'    => is_null($p->precio_pareja) ? null : (int) $p->precio_pareja,
                                    ];
                                })->values(),
                            ];
                        })->values(),
                    ];
                })
                ->values();
        }

        return view('themes.backoffice.pages.visita.create', [
            'reserva'               => $reserva,
            'ubicaciones'           => $ubicaciones,
            'lugares'               => LugarMasaje::all(),
            'servicios'             => $serviciosDisponibles,
            'horarios'              => $horariosDisponiblesSPA,
            'horasMasaje'           => $horariosDisponiblesMasajes,
            'entradas'              => $entradas,
            'fondos'                => $fondos,
            'acompañamientos'       => $acompañamientos,
            'masajesExtra'          => $masajesExtra,
            'almuerzosExtra'        => $almuerzosExtra,
            'cantidadMasajesExtra'  => $cantidadMasajesExtra,
            'incluyeMasajePrograma' => $incluyeMasajePrograma,
            'modoMasaje'            => $modoMasaje,
            'cantidadSlotsMasaje'   => $cantidadSlotsMasaje,
            'catalogoMasajes'       => $catalogoMasajes,
        ]);
    }

    private function slugTipoMasajeDesdeNombre(string $nombre): string
    {
        $n = mb_strtolower(trim($nombre));

        if (strpos($n, 'descontract') !== false) {
            return 'descontracturante';
        }

        if (strpos($n, 'relaj') !== false) {
            return 'relajacion';
        }

        if (strpos($n, 'prenatal') !== false) {
            return 'prenatal';
        }

        if (strpos($n, 'balin') !== false) {
            return 'balines';
        }

        // fallback: “slug” básico
        return str_slug($n);
    }
    /**
     * ✅ Calcula subtotal real por (tipo, duración, cantidad) usando PrecioTipoMasaje
     * Incluye regla “precio pareja” cuando exista (no solo relajación, sino cuando venga en BD).
     */
    private function subtotalMasaje(string $slugTipo, int $duracion, int $cantidad): int
    {
        $precio = PrecioTipoMasaje::with('tipo')
            ->whereHas('tipo', function ($q) use ($slugTipo) {
                $q->where('slug', $slugTipo);
            })
            ->where('duracion_minutos', $duracion)
            ->first();

        if (! $precio) {
            throw ValidationException::withMessages([
                'masajes' => "Tipo/duración inválida para masaje ({$slugTipo}, {$duracion} min).",
            ]);
        }

        $unit = (int) $precio->precio_unitario;
        $pair = $precio->precio_pareja !== null ? (int) $precio->precio_pareja : null;

        // Si hay precio pareja: aplica por cada 2
        if ($pair) {
            $pares = intdiv($cantidad, 2);
            $resto = $cantidad - ($pares * 2);
            return ($pares * $pair) + ($resto * $unit);
        }

        return $cantidad * $unit;
    }

    private function extraerPrecioIdSeleccionado($request): int
    {
        $precioId = (int) data_get($request->all(), 'masaje.precio_id', 0);
        if ($precioId > 0) {
            return $precioId;
        }

        $precioId = (int) collect((array) $request->input('masajes', []))
            ->pluck('precio_id')
            ->filter()
            ->first();

        return (int) $precioId;
    }

    /**
     * ✅ Calcula subtotal usando PrecioTipoMasaje por id (duración ya viene implícita)
     * Aplica regla precio_pareja cuando exista.
     */
    private function subtotalMasajePorPrecioId(int $precioTipoId, int $cantidad): int
    {
        $precio = PrecioTipoMasaje::query()
            ->select('id', 'precio_unitario', 'precio_pareja')
            ->where('id', $precioTipoId)
            ->first();

        if (! $precio) {
            throw ValidationException::withMessages([
                'masajes' => "Duración/Precio inválido (precio_id={$precioTipoId}).",
            ]);
        }

        $unit = (int) $precio->precio_unitario;
        $pair = $precio->precio_pareja !== null ? (int) $precio->precio_pareja : null;

        // Si existe precio pareja: cada 2 masajes usa $pair
        if ($pair) {
            $pares = intdiv($cantidad, 2);
            $resto = $cantidad - ($pares * 2);
            return ($pares * $pair) + ($resto * $unit);
        }

        return $cantidad * $unit;
    }

    /**
     * ✅ Recalcula subtotal y total_consumo desde detalle_servicios_extra (y/o lo que uses)
     * Ajusta aquí si tu total_consumo incluye IVA u otro cálculo.
     */
    // private function recalcularTotalesConsumo(int $consumoId): void
    // {
    //     $nuevoSubtotal = (int) DetalleServiciosExtra::where('id_consumo', $consumoId)->sum('subtotal');

    //     Consumo::where('id', $consumoId)->update([
    //         'subtotal'      => $nuevoSubtotal,
    //         'total_consumo' => $nuevoSubtotal, // ajusta si total_consumo = subtotal + IVA, etc.
    //     ]);
    // }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    public function show(Visita $visitum)
    {
        // $bytes = 1048580000;
        $bytes = $visitum->id;
        dd($this->formatBytes($bytes));
    }

    public function edit(Reserva $reserva, Visita $visita)
    {
        // $masajesExtra         = session()->get('masajesExtra');
        // $almuerzosExtra       = session()->get('almuerzosExtra');
        // $cantidadMasajesExtra = session()->get('cantidadMasajesExtra');

        // $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();
        // // $menus = $reserva->visitas->last()->menus;

        // // Obtenemos la fecha seleccionada del formulario
        // $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
        // $ubicacionesOcupadas = DB::table('visitas')
        //     ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
        //     ->join('ubicaciones', 'visitas.id_ubicacion', '=', 'ubicaciones.id')
        //     ->where('reservas.fecha_visita', $fechaSeleccionada)
        //     ->pluck('ubicaciones.nombre')
        //     ->map(function ($nombre) {
        //         return $nombre;
        //     })
        //     ->toArray();

        // $ubicacionesAll = DB::table('ubicaciones')
        //     ->select('id', 'nombre')
        //     ->get();

        // $ubicaciones = $ubicacionesAll->filter(function ($ubicacion) use ($ubicacionesOcupadas) {
        //     return ! in_array($ubicacion->nombre, $ubicacionesOcupadas);
        // })->values();

        // // ===============================HORAS=SPA==============================================
        // // Horarios disponibles de 10:00 a 18:30 SPA
        // $horaInicio = new \DateTime('10:00');
        // $horaFin    = new \DateTime('18:30');
        // $intervalo  = new \DateInterval('PT30M');
        // $horarios   = [];

        // while ($horaInicio <= $horaFin) {
        //     $horarios[] = $horaInicio->format('H:i');
        //     $horaInicio->add($intervalo);
        // }

        // // Obtener horarios ocupados de la tabla 'visitas'
        // $horariosOcupados = DB::table('visitas')
        //     ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
        //     ->where('reservas.fecha_visita', $fechaSeleccionada)
        //     ->pluck('visitas.horario_sauna')
        //     ->filter(function ($hora) {
        //         // Filtrar valores nulos o vacíos
        //         return ! is_null($hora) && $hora !== '';
        //     })
        //     ->map(function ($hora) {
        //         // Formatear solo los horarios válidos
        //         return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
        //     })
        //     ->toArray();

        // // Filtrar horarios disponibles
        // $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

        // //=================================HORAS=MASAJES=========================================

        // // Horarios disponibles de 10:20 a 19:00 con intervalos de 10 minutos entre sesiones de masaje
        // $horaInicioMasajes = new \DateTime('10:20');
        // $horaFinMasajes    = new \DateTime('19:00');
        // $duracionMasaje    = new \DateInterval('PT30M'); // 30 minutos de duración
        // $intervalos        = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
        // $horarios          = [];

        // while ($horaInicioMasajes <= $horaFinMasajes) {
        //     $horarios[] = $horaInicioMasajes->format('H:i');
        //     $horaInicioMasajes->add($duracionMasaje);
        //     $horaInicioMasajes->add($intervalos);
        // }

        // // Obtener las horas de inicio ocupadas de la tabla 'visitas' para masajes
        // $horariosOcupadosMasajes = DB::table('visitas')
        //     ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
        //     ->join('masajes as m', 'm.id_reserva', '=', 'reservas.id')
        //     ->where('reservas.fecha_visita', $fechaSeleccionada)
        //     ->whereNotNull('m.horario_masaje')
        //     ->select('m.horario_masaje', 'm.id_lugar_masaje')
        //     ->get()
        //     ->groupBy('id_lugar_masaje');

        // // Procesar horarios ocupados
        // $ocupadosPorLugar = [
        //     1 => [], // Containers
        //     2 => [], // Toldos
        // ];

        // foreach ($horariosOcupadosMasajes as $lugar => $visitas) {
        //     $ocupadosPorLugar[$lugar] = $visitas->pluck('horario_masaje')
        //         ->map(function ($hora) {
        //             return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
        //         })
        //         ->toArray();
        // }

        // // Filtrar horarios disponibles por lugar
        // $horariosDisponiblesMasajes = [
        //     1 => array_values(array_diff($horarios, $ocupadosPorLugar[1])), // Containers
        //     2 => array_values(array_diff($horarios, $ocupadosPorLugar[2])), // Toldos
        // ];

        // // // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        // // $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // // Obtener productos de tipo "entrada"
        // $entradas = Producto::activos()->whereHas('tipoProducto', function ($query) {
        //     $query->where('nombre', 'entrada');
        // })->get();

        // // Obtener productos de tipo "fondo"
        // $fondos = Producto::activos()->whereHas('tipoProducto', function ($query) {
        //     $query->where('nombre', 'fondo');
        // })->get();

        // // Obtener productos de tipo "postre"
        // $acompañamientos = Producto::activos()->whereHas('tipoProducto', function ($query) {
        //     $query->where('nombre', 'acompañamiento');
        // })->get();

        // // Obtener la última visita de la reserva
        // $ultimaVisita = $reserva->visitas->last();

        // // Obtener la cantidad de personas en la reserva
        // $cantidadPersonas = $reserva->cantidad_personas;

        // // Obtener los menús de la última visita
        // $menus = isset($reserva->menus) ? $reserva->menus : collect([]);

        // // Si la cantidad de menús es menor a la cantidad de personas en la reserva, agregamos menús vacíos
        // $menusFaltantes = $cantidadPersonas - $menus->count();
        // for ($i = 0; $i < $menusFaltantes; $i++) {
        //     $menus->push(new Menu()); // Agregar menú vacío
        // }

        // return view('themes.backoffice.pages.visita.edit', [
        //     'visita'          => $visita,
        //     'visitas'         => $reserva->visitas,
        //     'masajes'         => $reserva->masajes,
        //     'reserva'         => $reserva,
        //     'menus'           => $menus,
        //     'ubicaciones'     => $ubicaciones,
        //     'lugares'         => LugarMasaje::all(),
        //     'servicios'       => $serviciosDisponibles,
        //     'horarios'        => $horariosDisponiblesSPA,
        //     'horasMasaje'     => $horariosDisponiblesMasajes,
        //     'entradas'        => $entradas,
        //     'fondos'          => $fondos,
        //     'acompañamientos' => $acompañamientos,
        //     'masajesExtra'    => $masajesExtra,
        //     'almuerzosExtra'  => $almuerzosExtra,
        // ]);

    }

    public function update(UpdateRequest $request, Reserva $reserva)
    {
        // // Obtener cantidad de personas
        // $personas = session()->get('cantidadMasajesExtra') ?? $reserva->cantidad_masajes ?? $reserva->cantidad_personas;

        // $programa         = $reserva->programa;
        // $almuerzosExtra   = session()->get('almuerzosExtra');
        // $masajesExtra     = session()->get('masajesExtra');
        // $almuerzoIncluido = $programa->servicios->pluck('nombre_servicio')->toArray();

        // try {

        //     DB::transaction(function () use ($request, &$reserva, $almuerzoIncluido, $almuerzosExtra, $masajesExtra, $personas, $programa) {
        //         // Eliminar registros anteriores relacionados
        //         $reserva->visitas()->delete();
        //         $reserva->masajes()->delete();
        //         $reserva->menus()->delete();

        //         $cliente = $reserva->cliente;

        //         // Caso 1: Solo SPA (sin masajes)
        //         if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {

        //             $visita = $this->soloSpa($request, $reserva);
        //         }

        //         // Caso 2: 1 SPA + 1 horario Masaje
        //         if ($request->has('horario_masaje') && $request->has('horario_sauna')) {

        //             $visita = $this->spaConMasaje($request, $reserva, $personas);

        //         }

        //         // Caso 3: 1 horario SPA con arreglo de masajes
        //         if ($request->has('masajes') && $request->has('horario_sauna')) {

        //             $visita = $this->spaConMasajes($request, $reserva, $personas);
        //         }

        //         // Caso 4: Arreglos de SPA sin masajes
        //         if (! $request->has('masajes') && $request->has('spas')) {

        //             $visita = $this->spaSinMasajes($request, $reserva);
        //         }

        //         // Caso 5: Arreglos de SPA y masajes
        //         if ($request->has('masajes') && $request->has('spas')) {

        //             $visita = $this->spasConMasajes($request, $reserva, $personas);
        //         }

        //         // En caso de no registrar horarios
        //         $arrayMasajes  = $request->input('masajes', []);
        //         $arraySpas     = $request->input('spas', []);
        //         $incluyeMasaje = $reserva->programa->servicios->contains('nombre_servicio', 'Masaje') || $masajesExtra;

        //         // Validar que en los arreglos internos de `masajes` exista al menos una clave `horario_masaje`.
        //         $tieneHorarioMasaje = ! empty(array_filter($arrayMasajes, function ($item) {
        //             return is_array($item) && array_key_exists('horario_masaje', $item);
        //         }));

        //         // Caso 6: Sin data
        //         if ((empty($arrayMasajes) || ! $tieneHorarioMasaje) && empty($request->input('horario_masaje')) && empty($arraySpas) && empty($request->input('horario_sauna'))) {

        //             $this->sinData($request, $reserva, $incluyeMasaje, $personas);
        //         }

        //         // Menus
        //         if (in_array('Almuerzo', $almuerzoIncluido) || $almuerzosExtra) {

        //             foreach ($request->menus as $menu) {
        //                 Menu::create([
        //                     'id_reserva'                 => $reserva->id,
        //                     'id_producto_entrada'        => $menu['id_producto_entrada'] ?? null,
        //                     'id_producto_fondo'          => $menu['id_producto_fondo'] ?? null,
        //                     'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'] ?? null,
        //                     'alergias'                   => $menu['alergias'] ?? null,
        //                     'observacion'                => $menu['observacion'] ?? null,
        //                 ]);
        //             }
        //         }

        //         // if ($cliente && $visita) {
        //         //     Mail::to($cliente->correo)->send(new RegistroReservaMailable($visita, $reserva, $cliente, $programa));
        //         // }
        //     });

        //     Alert::success('Actualizado', 'La visita ha sido modificada correctamente')->showConfirmButton();
        //     return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva]);

        // } catch (\Exception $e) {
        //     Alert::error('Error', 'Debe completar todo el formulario o NO seleccionar nada')->showConfirmButton();
        //     return redirect()->back()->withInput();
        // }
    }

    public function destroy(Visita $visitum)
    {
        //
    }

    public function edit_ubicacion(Visita $visitum)
    {
        $fechaSeleccionada   = \Carbon\Carbon::createFromFormat('d-m-Y', $visitum->reserva->fecha_visita)->format('Y-m-d');
        $ubicacionesOcupadas = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->join('ubicaciones', 'visitas.id_ubicacion', '=', 'ubicaciones.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->pluck('ubicaciones.nombre')
            ->map(function ($nombre) {
                return $nombre;
            })
            ->toArray();

        $ubicacionesAll = DB::table('ubicaciones')
            ->select('id', 'nombre')
            ->get();

        $ubicaciones = $ubicacionesAll->filter(function ($ubicacion) use ($ubicacionesOcupadas) {
            return ! in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();

        return view('themes.backoffice.pages.visita.edit_ubicacion', [
            'visita'      => $visitum,
            'ubicaciones' => $ubicaciones,
        ]);

    }

    public function update_ubicacion(Request $request, Visita $visitum)
    {
        $ubicacionNueva = Ubicacion::where('id', '=', $request->ubicacion)
            ->first();
        $reserva = $visitum->reserva;
        $visitas = $reserva->visitas;
        foreach ($visitas as $visita) {
            $visita->update([
                'id_ubicacion' => $request->ubicacion,
            ]);
        }

        Alert::success('Éxito', 'Ubicacion cambiada a ' . $ubicacionNueva->nombre)->showConfirmButton('Confirmar');
        return redirect()->route('backoffice.reserva.show', ['reserva' => $visitum->id_reserva]);
    }

    public function register(Reserva $reserva, Visita $visita)
    {
        session()->get('masajesExtra') ? $masajesExtra                 = session()->get('masajesExtra') : $masajesExtra                 = null;
        session()->get('almuerzosExtra') ? $almuerzosExtra             = session()->get('almuerzosExtra') : $almuerzosExtra             = null;
        session()->get('cantidadMasajesExtra') ? $cantidadMasajesExtra = session()->get('cantidadMasajesExtra') : $cantidadMasajesExtra = null;

        $serviciosDisponibles = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();
        // Obtenemos la fecha seleccionada del formulario
        $fechaSeleccionada = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');

        $ubicacionesOcupadas = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->join('ubicaciones', 'visitas.id_ubicacion', '=', 'ubicaciones.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->pluck('ubicaciones.nombre')
            ->map(function ($nombre) {
                return $nombre;
            })
            ->toArray();

        $ubicacionesAll = DB::table('ubicaciones')
            ->select('id', 'nombre')
            ->get();

        $ubicaciones = $ubicacionesAll->filter(function ($ubicacion) use ($ubicacionesOcupadas) {
            return ! in_array($ubicacion->nombre, $ubicacionesOcupadas);
        })->values();

        // ===============================HORAS=SPA==============================================
        // Horarios disponibles de 10:00 a 18:30 SPA
        $horaInicio = new \DateTime('10:00');
        $horaFin    = new \DateTime('18:30');
        $intervalo  = new \DateInterval('PT30M');
        $horarios   = [];

        while ($horaInicio <= $horaFin) {
            $horarios[] = $horaInicio->format('H:i');
            $horaInicio->add($intervalo);
        }

        // Obtener horarios ocupados de la tabla 'visitas'
        $horariosOcupados = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->pluck('visitas.horario_sauna')
            ->filter(function ($hora) {
                // Filtrar valores nulos o vacíos
                return ! is_null($hora) && $hora !== '';
            })
            ->map(function ($hora) {
                // Formatear solo los horarios válidos
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

        //=================================HORAS=MASAJES=========================================

        // Horarios disponibles de 10:20 a 19:00 con intervalos de 10 minutos entre sesiones de masaje
        $horaInicioMasajes = new \DateTime('10:20');
        $horaFinMasajes    = new \DateTime('19:00');
        $duracionMasaje    = new \DateInterval('PT30M'); // 30 minutos de duración
        $intervalos        = new \DateInterval('PT10M'); // 10 minutos de intervalos entre sesiones
        $horarios          = [];

        while ($horaInicioMasajes <= $horaFinMasajes) {
            $horarios[] = $horaInicioMasajes->format('H:i');
            $horaInicioMasajes->add($duracionMasaje);
            $horaInicioMasajes->add($intervalos);
        }

        // Obtener las horas de inicio ocupadas de la tabla 'visitas' para masajes
        $horariosOcupadosMasajes = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->join('masajes as m', 'm.id_visita', '=', 'visitas.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->whereNotNull('m.horario_masaje')
            ->select('m.horario_masaje', 'm.id_lugar_masaje')
            ->get()
            ->groupBy('id_lugar_masaje');

        // Procesar horarios ocupados
        $ocupadosPorLugar = [
            1 => [], // Containers
            2 => [], // Toldos
        ];

        foreach ($horariosOcupadosMasajes as $lugar => $visitas) {
            $ocupadosPorLugar[$lugar] = $visitas->pluck('horario_masaje')
                ->map(function ($hora) {
                    return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
                })
                ->toArray();
        }

        // Filtrar horarios disponibles por lugar
        $horariosDisponiblesMasajes = [
            1 => array_values(array_diff($horarios, $ocupadosPorLugar[1])), // Containers
            2 => array_values(array_diff($horarios, $ocupadosPorLugar[2])), // Toldos
        ];

        // // Filtrar horarios disponibles (ajusta si ocupas rangos completos)
        // $horariosDisponiblesMasajes = array_diff($horarios, $horariosOcupadosMasajes);

        // Obtener productos de tipo "entrada"
        $entradas = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        return view('themes.backoffice.pages.visita.register', [
            'visita'          => $visita,
            'reserva'         => $reserva,
            'ubicaciones'     => $ubicaciones,
            'lugares'         => LugarMasaje::all(),
            'servicios'       => $serviciosDisponibles,
            'horarios'        => $horariosDisponiblesSPA,
            'horasMasaje'     => $horariosDisponiblesMasajes,
            'entradas'        => $entradas,
            'fondos'          => $fondos,
            'acompañamientos' => $acompañamientos,
            'masajesExtra'    => $masajesExtra,
            'almuerzosExtra'  => $almuerzosExtra,
        ]);
    }

    public function register_update(Request $request, Reserva $reserva, Visita $visita)
    {

        $menusActuales   = Menu::where('id_visita', $reserva->visitas->last()->id)->get()->keyBy('id');
        $visitasActuales = Visita::where('id_reserva', $reserva->id)->get()->keyBy('id');
        $masajesActuales = Masaje::where('id_visita', $reserva->visitas->last()->id)->get()->keyBy('id');

        // dd($visitasActuales, $visita);

        if (session()->get('cantidadMasajesExtra') !== null) {
            $personas = session()->get('cantidadMasajesExtra');
        } elseif ($reserva->cantidad_masajes !== null) {
            $personas = $reserva->cantidad_masajes;
        } else {
            $personas = $reserva->cantidad_personas;
        }

        $cliente        = null;
        $programa       = $reserva->programa;
        $almuerzosExtra = session()->get('almuerzosExtra');
        $masajesExtra   = session()->get('masajesExtra');

        $almuerzoIncluido = $programa->servicios->pluck('nombre_servicio')->toArray();

        try {
            DB::transaction(function () use ($request, &$reserva, &$visita, &$cliente, $almuerzoIncluido, $almuerzosExtra, $personas, &$menusActuales, &$visitasActuales, &$masajesActuales, $masajesExtra) {

                $cliente = $reserva->cliente;

                // Caso 1: Solo SPA (sin masajes)
                if (! $request->has('masajes') && ! $request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Crear una visita con solo SPA
                    $visita->update([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,
                        'horario_tinaja' => $horarioTinaja,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);
                }

                // Caso 2: 1 SPA + 1 horario Masaje
                if ($request->has('horario_masaje') && $request->has('horario_sauna')) {
                    // Convertir horario_sauna a objeto Carbon
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                    $horarioMasaje = Carbon::createFromFormat('H:i', $request->input('horario_masaje'));

                    // Crear una visita con solo SPA
                    $visita->update([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,
                        'horario_tinaja' => $horarioTinaja,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);

                    $masajes = Masaje::where('id_visita', $visita->id)->get();

                    foreach ($masajes as $masaje) {

                        $masaje->update([
                            'horario_masaje'  => $horarioMasaje,
                            'tipo_masaje'     => $request->input('tipo_masaje'),
                            'id_lugar_masaje' => $request->input('id_lugar_masaje'),
                        ]);
                    }

                }

                // Caso 3: 1 horario SPA con arreglo de masajes
                if ($request->has('masajes') && $request->has('horario_sauna')) {
                    // Obtener horario de sauna
                    $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

                    // Inicializar variables
                    $masajes               = array_values($request->input('masajes'));
                    $masajesActuales       = $masajesActuales->values();
                    $contadorPersonas      = 1; // Contador de personas que reciben masaje
                    $maxPersonasPorHorario = 2; // Máximo de personas por cada horario de masaje
                    $totalMasajes          = $personas;

                    // Actualizar el SPA
                    $visita->update([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => $horarioSauna,
                        'horario_tinaja' => $horarioTinaja,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);

                                             // Procesar los masajes (Pendiente)
                    $indiceMasajeActual = 0; // Para recorrer los registros en la base de datos

                    foreach ($masajes as $index => $masajeData) {
                        for ($i = 1; $i <= 2; $i++) { // Crear dos registros por cada "Par" en el formulario
                            if (! isset($masajesActuales[$indiceMasajeActual])) {
                                break; // Evitar errores de índice si hay menos registros de los esperados
                            }

                            $masaje = $masajesActuales[$indiceMasajeActual];

                            try {
                                $horarioMasaje = Carbon::createFromFormat('H:i', $masajeData['horario_masaje']);
                            } catch (\Exception $e) {
                                continue; // Saltar en caso de error
                            }

                            // Actualizar el registro de masaje
                            $masaje->update([
                                'horario_masaje'  => $horarioMasaje,
                                'tipo_masaje'     => $masajeData['tipo_masaje'],
                                'id_lugar_masaje' => $masajeData['id_lugar_masaje'] ?? null,
                            ]);

                            $indiceMasajeActual++;
                        }
                    }
                }

                // Caso 4: Arreglos de SPA sin masajes
                if (! $request->has('masajes') && $request->has('spas')) {
                    $spas            = array_values($request->input('spas'));
                    $visitasActuales = $visitasActuales->values();

                    foreach ($visitasActuales as $index => $visita) {
                        // Asegúrate de que el índice `$index` existe en `$spas`
                        if (isset($spas[$index])) {
                            $spa = $spas[$index];

                            // Validar que el horario_sauna exista en los datos del request
                            if (isset($spa['horario_sauna'])) {
                                try {
                                    $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                                } catch (\Exception $e) {
                                    // Manejar error de formato si es necesario
                                    continue;
                                }

                                // Actualizar la visita correspondiente
                                $visita->update([
                                    'id_reserva'     => $reserva->id,
                                    'horario_sauna'  => $horarioSauna,
                                    'horario_tinaja' => $horarioTinaja,
                                    'id_ubicacion'   => $request->input('id_ubicacion'),
                                    'trago_cortesia' => $request->input('trago_cortesia'),
                                    'observacion'    => $request->input('observacion'),
                                ]);
                            }
                        }
                    }
                }

                // Caso 5: Arreglos de SPA y masajes
                if ($request->has('masajes') && $request->has('spas')) {
                    // Inicializar variables
                    $spas                  = array_values($request->input('spas'));
                    $masajes               = array_values($request->input('masajes'));
                    $contadorPersonas      = 1;
                    $maxPersonasPorHorario = 2;
                    $totalMasajes          = $personas;

                    $visitasActuales = $visitasActuales->values();
                    $masajesActuales = $masajesActuales->values();

                    foreach ($visitasActuales as $index => $visita) {
                        // Asegúrate de que el índice `$index` existe en `$spas`
                        if (isset($spas[$index])) {
                            $spa = $spas[$index];

                            // Validar que el horario_sauna exista en los datos del request
                            if (isset($spa['horario_sauna'])) {
                                try {
                                    $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                                    $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
                                } catch (\Exception $e) {
                                    // Manejar error de formato si es necesario
                                    continue;
                                }

                                // Actualizar la visita correspondiente
                                $visita->update([
                                    'id_reserva'     => $reserva->id,
                                    'horario_sauna'  => $horarioSauna,
                                    'horario_tinaja' => $horarioTinaja,
                                    'id_ubicacion'   => $request->input('id_ubicacion'),
                                    'trago_cortesia' => $request->input('trago_cortesia'),
                                    'observacion'    => $request->input('observacion'),
                                ]);
                            }
                        }
                    }

                    $indiceMasajeActual = 0; // Para recorrer los registros en la base de datos

                    foreach ($masajes as $index => $masajeData) {
                        for ($i = 1; $i <= 2; $i++) { // Crear dos registros por cada "Par" en el formulario
                            if (! isset($masajesActuales[$indiceMasajeActual])) {
                                break; // Evitar errores de índice si hay menos registros de los esperados
                            }

                            $masaje = $masajesActuales[$indiceMasajeActual];

                            try {
                                $horarioMasaje = Carbon::createFromFormat('H:i', $masajeData['horario_masaje']);
                            } catch (\Exception $e) {
                                continue; // Saltar en caso de error
                            }

                            // Actualizar el registro de masaje
                            $masaje->update([
                                'horario_masaje'  => $horarioMasaje,
                                'tipo_masaje'     => $masajeData['tipo_masaje'],
                                'id_lugar_masaje' => $masajeData['id_lugar_masaje'] ?? null,
                            ]);

                            $indiceMasajeActual++; // Avanzar en los registros de la base de datos
                        }
                    }

                }

                // Menus
                if (in_array('Almuerzo', $almuerzoIncluido) || $almuerzosExtra) {

                    $menusActuales = $menusActuales->values();
                    $menus         = array_values($request->menus);

                    foreach ($menusActuales as $index => $menu) {
                        if (isset($menus[$index])) {
                            $menuData = $menus[$index];

                            if (! isset($menuData['id_producto_entrada']) && ! isset($menuData['id_producto_fondo'])) {
                                continue;
                            }

                            $menu->update([
                                'id_producto_entrada'        => $menuData['id_producto_entrada'] ?? null,
                                'id_producto_fondo'          => $menuData['id_producto_fondo'] ?? null,
                                'id_producto_acompanamiento' => $menuData['id_producto_acompanamiento'] ?? null,
                                'alergias'                   => $menuData['alergias'] ?? null,
                                'observacion'                => $menuData['observacion'] ?? null,
                            ]);

                        } else {
                            Alert::toast('Surgió un problema con los menús', 'error')->toToast('center');
                            return redirect()->back();
                        }
                    }
                }

            });

            if ($cliente && $visita) {
                Mail::to($cliente->correo)->send(new RegistroReservaMailable($visita, $reserva, $cliente, $programa));
            }

            Alert::success('Éxito', 'Se ha generado la visita')->showConfirmButton();

            session()->forget(['masajesExtra', 'almuerzosExtra']);

            return redirect()->route('backoffice.reserva.show', ['reserva' => $request->input('id_reserva')]);

        } catch (\Exception $e) {
            Alert::error('Error', 'Algo salio mal, intente nuevamente. ' . $e)->showConfirmButton();
            return redirect()->back()->withInput();
        }
    }

    public function menu(Reserva $reserva, Visita $visita)
    {
        $servicios = $reserva->programa->servicios->pluck('nombre_servicio')->toArray();
        $menus     = $reserva->menus;

        $almuerzosExtra = null;

        if (in_array('Almuerzo', $servicios)) {
            $almuerzosExtra = false;
        } else {
            $almuerzosExtra = isset($menus);
        }

        // Obtener productos de tipo "entrada"
        $entradas = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'entrada');
        })->get();

        // Obtener productos de tipo "fondo"
        $fondos = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'fondo');
        })->get();

        // Obtener productos de tipo "postre"
        $acompañamientos = Producto::activos()->whereHas('tipoProducto', function ($query) {
            $query->where('nombre', 'acompañamiento');
        })->get();

        return view('themes.backoffice.pages.visita.menu.edit', [
            'reserva'         => $reserva,
            'visita'          => $visita,
            'servicios'       => $servicios,
            'menus'           => $menus,
            'entradas'        => $entradas,
            'fondos'          => $fondos,
            'acompañamientos' => $acompañamientos,
            'almuerzosExtra'  => $almuerzosExtra,
        ]);
    }

    public function menu_update(Request $request, Reserva $reserva, Visita $visita)
    {
        $request->validate([
            'menus.*.id_producto_entrada'        => 'required|integer|exists:productos,id',
            'menus.*.id_producto_fondo'          => 'required|integer|exists:productos,id',
            'menus.*.id_producto_acompanamiento' => 'nullable|integer|exists:productos,id',
            'menus.*.alergias'                   => 'nullable|string',
            'menus.*.observacion'                => 'nullable|string',
        ]);

        try {

            foreach ($request->menus as $id => $datos) {
                $menu = Menu::findOrFail($id);

                $menu->update([
                    'id_producto_entrada'        => $datos['id_producto_entrada'],
                    'id_producto_fondo'          => $datos['id_producto_fondo'],
                    'id_producto_acompanamiento' => $datos['id_producto_acompanamiento'],
                    'alergias'                   => $datos['alergias'],
                    'observacion'                => $datos['observacion'],
                ]);
            }

            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva])->with('success', 'Menús actualizados correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar los menús. ' . $e->getMessage());
        }
    }

    public function spa(Reserva $reserva, Visita $visita)
    {
        $spas = $reserva->visitas;

        $fechaSeleccionada = \Carbon\Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');

        $horaInicio = new \DateTime('10:00');
        $horaFin    = new \DateTime('18:30');
        $intervalo  = new \DateInterval('PT30M');
        $horarios   = [];

        while ($horaInicio <= $horaFin) {
            $horarios[] = $horaInicio->format('H:i');
            $horaInicio->add($intervalo);
        }

        // Obtener horarios ocupados de la tabla 'visitas'
        $horariosOcupados = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->where('reservas.fecha_visita', $fechaSeleccionada)
            ->pluck('visitas.horario_sauna')
            ->filter(function ($hora) {
                // Filtrar valores nulos o vacíos
                return ! is_null($hora) && $hora !== '';
            })
            ->map(function ($hora) {
                // Formatear solo los horarios válidos
                return \Carbon\Carbon::createFromFormat('H:i:s', $hora)->format('H:i');
            })
            ->toArray();

        // Filtrar horarios disponibles
        $horariosDisponiblesSPA = array_diff($horarios, $horariosOcupados);

        return view('themes.backoffice.pages.visita.spa.edit', [
            'reserva'  => $reserva,
            'visita'   => $visita,
            'spas'     => $spas,
            'horarios' => $horariosDisponiblesSPA,
        ]);
    }

    public function spa_update(Request $request, Reserva $reserva)
    {
        $request->validate([
            'spas.*.horario_sauna' => 'required|string',
            'spas.*.observacion'   => 'nullable|string',
            'trago_cortesia'       => 'required|string',
        ]);

        try {
            foreach ($request->spas as $id => $spa) {
                $horaSpa    = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
                $horaTinaja = $horaSpa->copy()->addMinutes(15);

                $visita = Visita::findOrFail($id);

                $visita->update([
                    'horario_sauna'  => $horaSpa,
                    'horario_tinaja' => $horaTinaja,
                    'trago_cortesia' => $request->input('trago_cortesia'),
                    'observacion'    => $spa['observacion'],
                ]);
            }

            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva])->with('success', 'Horarios SPA actualizados correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar los horarios de SPA.');
        }

    }

    // private function soloSpa(Request $request, Reserva $reserva): void
    // {
    //     // Convertir horario_sauna a objeto Carbon
    //     $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
    //     $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

    //     // Crear una visita con solo SPA
    //     Visita::create([
    //         'id_reserva'     => $reserva->id,
    //         'horario_sauna'  => $horarioSauna,  // Horario del SPA
    //         'horario_tinaja' => $horarioTinaja, // Horario de tinaja
    //         'id_ubicacion'   => $request->input('id_ubicacion'),
    //         'trago_cortesia' => $request->input('trago_cortesia'),
    //         'observacion'    => $request->input('observacion'),
    //     ]);

    //     session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);

    // }

    // private function spaConMasaje(Request $request, Reserva $reserva, $personas): void
    // {
    //     // Convertir horario_sauna a objeto Carbon
    //     $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
    //     $horarioTinaja = $horarioSauna->copy()->addMinutes(15);
    //     $horarioMasaje = Carbon::createFromFormat('H:i', $request->input('horario_masaje'));

    //     // Crear una visita con solo SPA
    //     Visita::create([
    //         'id_reserva'     => $reserva->id,
    //         'horario_sauna'  => $horarioSauna,  // Horario del SPA
    //         'horario_tinaja' => $horarioTinaja, // Horario de tinaja
    //         'id_ubicacion'   => $request->input('id_ubicacion'),
    //         'trago_cortesia' => $request->input('trago_cortesia'),
    //         'observacion'    => $request->input('observacion'),
    //     ]);

    //     for ($i = 1; $i <= $personas; $i++) {
    //         Masaje::create([
    //             'horario_masaje'  => $horarioMasaje, // Horario de masaje
    //             'tipo_masaje'     => $request->input('tipo_masaje'),
    //             'id_lugar_masaje' => $request->input('id_lugar_masaje') ?? 1,
    //             'persona'         => $i,
    //             'id_reserva'      => $reserva->id,
    //         ]);
    //     }

    //     session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
    // }

    // private function spaConMasajes(Request $request, Reserva $reserva, $personas): void
    // {
    //     // Obtener horario de sauna
    //     $horarioSauna  = Carbon::createFromFormat('H:i', $request->input('horario_sauna'));
    //     $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

    //     // Inicializar variables
    //     $masajes               = $request->input('masajes');
    //     $contadorPersonas      = 1; // Contador de personas que reciben masaje
    //     $maxPersonasPorHorario = 2; // Máximo de personas por cada horario de masaje
    //     $totalMasajes          = $personas;

    //     // Crear la visita una sola vez
    //     Visita::create([
    //         'id_reserva'     => $reserva->id,
    //         'horario_sauna'  => $horarioSauna,
    //         'horario_tinaja' => $horarioTinaja,
    //         'id_ubicacion'   => $request->input('id_ubicacion'),
    //         'trago_cortesia' => $request->input('trago_cortesia'),
    //         'observacion'    => $request->input('observacion'),
    //     ]);

    //     // Procesar los masajes
    //     foreach ($masajes as $index => $horario) {
    //         for ($i = 1; $i <= $maxPersonasPorHorario; $i++) {
    //             if ($contadorPersonas > $totalMasajes) {
    //                 break;
    //             }

    //             Masaje::create([
    //                 'horario_masaje'  => Carbon::createFromFormat('H:i', $horario['horario_masaje']),
    //                 'tipo_masaje'     => $horario['tipo_masaje'],
    //                 'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? 1,
    //                 'persona'         => $contadorPersonas,
    //                 'id_reserva'      => $reserva->id,
    //             ]);
    //             $contadorPersonas++;

    //         }
    //     }

    //     session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
    // }

    // private function spaSinMasajes(Request $request, Reserva $reserva): void
    // {
    //     foreach ($request->input('spas') as $indexSpa => $spa) {
    //         // Validar que el horario_sauna exista en el arreglo actual
    //         if (isset($spa['horario_sauna'])) {
    //             $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
    //             $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

    //             // Crear una visita para cada SPA
    //             Visita::create([
    //                 'id_reserva'     => $reserva->id,
    //                 'horario_sauna'  => $horarioSauna,
    //                 'horario_tinaja' => $horarioTinaja,
    //                 'id_ubicacion'   => $request->input('id_ubicacion'),
    //                 'trago_cortesia' => $request->input('trago_cortesia'),
    //                 'observacion'    => $request->input('observacion'),
    //             ]);

    //         }
    //     }

    //     session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
    // }

    // private function spasConMasajes(Request $request, Reserva $reserva, $personas): void
    // {
    //     // Inicializar variables
    //     $masajes               = $request->input('masajes');
    //     $contadorPersonas      = 1; // Contador de personas que reciben masaje
    //     $maxPersonasPorHorario = 2; // Máximo de personas por cada horario de masaje
    //     $totalMasajes          = $personas;

    //     //Procesar los horarios SPA
    //     foreach ($request->input('spas') as $indexSpa => $spa) {
    //         // Validar que el horario_sauna exista en el arreglo actual
    //         if (isset($spa['horario_sauna'])) {
    //             $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
    //             $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

    //             // Crear una visita para cada SPA
    //             Visita::create([
    //                 'id_reserva'     => $reserva->id,
    //                 'horario_sauna'  => $horarioSauna,
    //                 'horario_tinaja' => $horarioTinaja,
    //                 'id_ubicacion'   => $request->input('id_ubicacion'),
    //                 'trago_cortesia' => $request->input('trago_cortesia'),
    //                 'observacion'    => $request->input('observacion'),
    //             ]);

    //         }
    //     }

    //     // Procesar los masajes
    //     foreach ($masajes as $index => $horario) {
    //         for ($i = 1; $i <= $maxPersonasPorHorario; $i++) {
    //             if ($contadorPersonas > $totalMasajes) {
    //                 break;
    //             }

    //             Masaje::create([
    //                 'horario_masaje'  => Carbon::createFromFormat('H:i', $horario['horario_masaje']),
    //                 'tipo_masaje'     => $horario['tipo_masaje'],
    //                 'id_lugar_masaje' => $horario['id_lugar_masaje'] ?? 1,
    //                 'persona'         => $contadorPersonas,
    //                 'id_reserva'      => $reserva->id,
    //             ]);
    //             $contadorPersonas++;

    //         }
    //     }

    //     session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);
    // }

    // private function sinData(Request $request, Reserva $reserva, $incluyeMasaje, $personas): void
    // {
    //     $cantidadPersonas     = $reserva->cantidad_personas;
    //     $maxPersonasPorVisita = 5;
    //     $visita               = null;

    //     for ($i = 1; $i <= ceil($cantidadPersonas / $maxPersonasPorVisita); $i++) {
    //         Visita::create([
    //             'horario_sauna'  => null,
    //             'horario_tinaja' => null,
    //             'trago_cortesia' => $request->input('trago_cortesia') ?? null,
    //             'observacion'    => null,
    //             'id_reserva'     => $reserva->id,
    //             'id_ubicacion'   => $request->input('id_ubicacion') ?? null,
    //         ]);
    //     }

    //     if ($incluyeMasaje) {
    //         for ($i = 1; $i <= $personas; $i++) {
    //             Masaje::create([
    //                 'horario_masaje'  => null,
    //                 'tipo_masaje'     => null,
    //                 'id_lugar_masaje' => 1,
    //                 'persona'         => $i,
    //                 'id_reserva'      => $reserva->id,
    //                 'user_id'         => null,
    //             ]);
    //         }
    //     }
    // }

    // ======================= STORE =========================

    public function store(StoreRequest $request, Reserva $reserva)
    {
        // dd($request->all());    

        // ===== Personas para MASAJES (NO confundir con asistentes para MENÚ) =====
        $personasMasaje = $this->resolverCantidadMasajes($reserva);

        $cliente  = null;
        $visita   = null;
        $programa = $reserva->programa;

        $almuerzosExtra = (bool) session()->get('almuerzosExtra');
        $masajesExtra   = (bool) session()->get('masajesExtra');

        $serviciosPrograma = $programa->servicios->pluck('nombre_servicio')->toArray();
        $incluyeAlmuerzo   = in_array('Almuerzo', $serviciosPrograma) || $almuerzosExtra;
        $incluyeMasaje     = $programa->servicios->contains('nombre_servicio', 'Masaje') || $masajesExtra;

        try {
            DB::transaction(function () use (
                $request,
                $reserva,
                $programa,
                &$cliente,
                &$visita,
                $incluyeAlmuerzo,
                $incluyeMasaje,
                $personasMasaje
            ) {

                $cliente = $reserva->cliente;

                // ==========================
                // 1) Normalizar payload SPA
                // ==========================
                // Puede venir:
                // - horario_sauna (singular)
                // - spas[][horario_sauna] (multiple)
                $spas = $this->normalizarSpas($request);

                // =============================
                // 2) Normalizar payload MASAJES
                // =============================
                // Puede venir:
                // - (<=2) horario_masaje + masaje[tipo_slug|precio_id] (extra) OR tipo_masaje fijo (programa)
                // - masajes[][...] (incluidos o extra)
                $masajesNorm = $this->normalizarMasajes($request);

                // Flags de presencia “real” (no solo keys vacías)
                $tieneSpaReal    = $this->tieneSpaReal($spas);
                $tieneMasajeReal = $this->tieneMasajeReal($masajesNorm);

                // ==========================================================
                // 3) Si NO se registra ningún dato de horarios (cliente decide)
                //    -> Crear visitas placeholder (por grupos de 5 asistentes)
                //    -> Crear masajes placeholder si corresponde (incluyeMasaje)
                //    -> Crear menús (si corresponde)
                // ==========================================================
                if (! $tieneSpaReal && ! $tieneMasajeReal) {

                    $this->crearVisitasPlaceholder($request, $reserva);

                    if ($incluyeMasaje) {
                        $this->crearMasajesPlaceholder($reserva, $personasMasaje);
                    }

                    if ($incluyeAlmuerzo) {
                        $this->crearMenusSiCorresponde($request, $reserva);
                    }

                    // Si estás usando sesión para extras/almuerzos, límpiala igual
                    session()->forget(['masajesExtra', 'almuerzosExtra', 'cantidadMasajesExtra']);

                    // No hay cobro extra aquí porque no se definieron precio_id
                    return;
                }

                // =========================================
                // 4) Crear Visitas (SPA) si hay horarios SPA
                // =========================================
                // Si hay 1 SPA singular => crea una visita
                // Si hay múltiples => crea una por cada grupo
                if ($tieneSpaReal) {
                    $visita = $this->crearVisitasDesdeSpa($request, $reserva, $spas);
                } else {
                    // Si no hay spa real pero sí hay masajes, igual debes tener al menos 1 visita (placeholder de visita)
                    // Mantengo tu comportamiento: crear una visita con horario_sauna/tinaja null.
                    $visita = Visita::create([
                        'id_reserva'     => $reserva->id,
                        'horario_sauna'  => null,
                        'horario_tinaja' => null,
                        'id_ubicacion'   => $request->input('id_ubicacion'),
                        'trago_cortesia' => $request->input('trago_cortesia'),
                        'observacion'    => $request->input('observacion'),
                    ]);
                }

                // ======================================
                // 5) Crear Masajes si hay masajes reales
                // ======================================
                if ($tieneMasajeReal) {
                    $this->crearMasajesDesdePayload($reserva, $masajesNorm, $personasMasaje);
                } else {
                    // Si no hay masajes reales pero el programa incluye masaje, deja placeholders.
                    if ($incluyeMasaje) {
                        $this->crearMasajesPlaceholder($reserva, $personasMasaje);
                    }
                }

                // =======================
                // 6) Menús (si corresponde)
                // =======================
                if ($incluyeAlmuerzo) {
                    $this->crearMenusSiCorresponde($request, $reserva);
                }

                // ==========================================
                // 7) Cobro de MASAJES EXTRA (detalle_extra)
                // ==========================================
                // Reglas:
                // - Solo si en payload vienen precio_id (modo extra)
                // - Agrupar por precio_id
                // - Aplicar pareja si existe
                $this->procesarCobroMasajesExtra($request, $reserva);

                session()->forget(['almuerzosExtra']);
            });

            // Enviar mail SOLO si se creó al menos una visita (placeholder o real)
            $cliente = $cliente ?? $reserva->cliente;
            $visita  = $visita ?? Visita::where('id_reserva', $reserva->id)->latest('id')->first();

            if ($cliente && $visita) {
                Mail::to($cliente->correo)->send(new RegistroReservaMailable($visita, $reserva, $cliente, $programa));
            }

            return redirect()
                ->route('backoffice.reserva.show', ['reserva' => $reserva])
                ->with('success', 'Se ha generado la visita.');

        } catch (\Throwable $e) {

            Log::error('Error al generar visita en store()', [
                'reserva_id'   => $reserva->id ?? null,
                'message'      => $e->getMessage(),
                'exception'    => $e,
                'request_data' => $request->all(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Debe completar todo el formulario o NO seleccionar nada.');
        }
    }

    // ======================= HELPERS STORE=========================

    private function resolverCantidadMasajes(Reserva $reserva): int
    {
        if (session()->get('cantidadMasajesExtra') !== null) {
            return (int) session()->get('cantidadMasajesExtra');
        }

        if (! is_null($reserva->cantidad_masajes)) {
            return (int) $reserva->cantidad_masajes;
        }

        return (int) $reserva->cantidad_personas;
    }

    private function normalizarSpas($request): array
    {
        // Retorna array de items: [ ['horario_sauna' => 'HH:ii'] , ... ]
        $spas = [];

        if ($request->has('spas') && is_array($request->input('spas'))) {
            foreach ($request->input('spas') as $spa) {
                $spas[] = [
                    'horario_sauna' => Arr::get($spa, 'horario_sauna'),
                ];
            }
            return $spas;
        }

        if ($request->filled('horario_sauna')) {
            $spas[] = [
                'horario_sauna' => $request->input('horario_sauna'),
            ];
        }

        return $spas;
    }

    private function tieneSpaReal(array $spas): bool
    {
        foreach ($spas as $spa) {
            if (! empty($spa['horario_sauna'])) {
                return true;
            }

        }
        return false;
    }

    private function normalizarMasajes($request): array
    {
        // Retorna array de items normalizados.
        // Modos:
        // - extra: trae precio_id + tipo_slug + categoria_slug (opcional)
        // - incluido: trae tipo_masaje (texto) + tiempo_extra (0/1) (opcional)
        //
        // Campos normalizados:
        // [
        //   'horario_masaje' => 'HH:ii'|null,
        //   'id_lugar_masaje' => int|null,
        //   'modo' => 'extra'|'incluido',
        //   'tipo_slug' => string|null,
        //   'precio_id' => int|null,
        //   'tipo_masaje' => string|null,
        //   'tiempo_extra' => int|bool|null,
        // ]
        $out = [];

        // Caso multi: masajes[1..n][...]
        $masajes = $request->input('masajes', null);
        if (is_array($masajes) && ! empty($masajes)) {
            foreach ($masajes as $i => $m) {
                if (! is_array($m)) {
                    continue;
                }

                $out[] = [
                    'horario_masaje'  => Arr::get($m, 'horario_masaje'),
                    'id_lugar_masaje' => Arr::get($m, 'id_lugar_masaje'),
                    'modo'            => Arr::has($m, 'precio_id') ? 'extra' : 'incluido',
                    'tipo_slug'       => Arr::get($m, 'tipo_slug'),
                    'precio_id'       => Arr::get($m, 'precio_id'),
                    'tipo_masaje'     => Arr::get($m, 'tipo_masaje'),
                    'tiempo_extra'    => Arr::get($m, 'tiempo_extra'),
                ];
            }
            return $out;
        }

        // Caso singular (<=2): horario_masaje + (masaje[precio_id...]) o tipo_masaje fijo
        if ($request->has('horario_masaje') || $request->has('masaje') || $request->has('tipo_masaje')) {

            $horario = $request->input('horario_masaje');
            $lugar   = $request->input('id_lugar_masaje');

            // Si viene masaje[precio_id] => extra
            if (is_array($request->input('masaje')) && Arr::has($request->input('masaje'), 'precio_id')) {
                $out[] = [
                    'horario_masaje'  => $horario,
                    'id_lugar_masaje' => $lugar,
                    'modo'            => 'extra',
                    'tipo_slug'       => Arr::get($request->input('masaje'), 'tipo_slug'),
                    'precio_id'       => Arr::get($request->input('masaje'), 'precio_id'),
                    'tipo_masaje'     => null,
                    'tiempo_extra'    => null,
                ];
                return $out;
            }

            // Si viene tipo_masaje => incluido (programa)
            if ($request->filled('tipo_masaje')) {
                $out[] = [
                    'horario_masaje'  => $horario,
                    'id_lugar_masaje' => $lugar,
                    'modo'            => 'incluido',
                    'tipo_slug'       => null,
                    'precio_id'       => null,
                    'tipo_masaje'     => $request->input('tipo_masaje'),
                    'tiempo_extra'    => $request->input('tiempo_extra', 0),
                ];
                return $out;
            }
        }

        return $out;
    }

    private function tieneMasajeReal(array $masajesNorm): bool
    {
        foreach ($masajesNorm as $m) {
            if (! empty($m['horario_masaje'])) {
                return true;
            }

        }
        return false;
    }

    private function crearVisitasPlaceholder($request, Reserva $reserva): void
    {
        $cantidadPersonas     = (int) $reserva->cantidad_personas;
        $maxPersonasPorVisita = 5;
        $grupos               = (int) ceil($cantidadPersonas / $maxPersonasPorVisita);

        for ($i = 1; $i <= $grupos; $i++) {
            Visita::create([
                'id_reserva'     => $reserva->id,
                'horario_sauna'  => null,
                'horario_tinaja' => null,
                'trago_cortesia' => $request->input('trago_cortesia') ?? null,
                'observacion'    => null,
                'id_ubicacion'   => $request->input('id_ubicacion') ?? null,
            ]);
        }
    }

    private function crearVisitasDesdeSpa($request, Reserva $reserva, array $spas): ?Visita
    {
        $ultima = null;

        foreach ($spas as $spa) {
            if (empty($spa['horario_sauna'])) {
                continue;
            }

            $horarioSauna  = Carbon::createFromFormat('H:i', $spa['horario_sauna']);
            $horarioTinaja = $horarioSauna->copy()->addMinutes(15);

            $ultima = Visita::create([
                'id_reserva'     => $reserva->id,
                'horario_sauna'  => $horarioSauna,
                'horario_tinaja' => $horarioTinaja,
                'id_ubicacion'   => $request->input('id_ubicacion'),
                'trago_cortesia' => $request->input('trago_cortesia'),
                'observacion'    => $request->input('observacion'),
            ]);
        }

        return $ultima;
    }

    private function crearMasajesPlaceholder(Reserva $reserva, int $personasMasaje): void
    {
        for ($i = 1; $i <= $personasMasaje; $i++) {
            Masaje::create([
                'horario_masaje'  => null,
                'tipo_masaje'     => null,
                'id_lugar_masaje' => 1,
                'persona'         => $i,
                'tiempo_extra'    => null,
                'id_reserva'      => $reserva->id,
                'user_id'         => null,
            ]);
        }
    }

    private function crearMasajesDesdePayload(Reserva $reserva, array $masajesNorm, int $personasMasaje): void
    {
        // Detecta si payload es “extra por persona” (trae precio_id en la mayoría)
        $modo = collect($masajesNorm)->contains(function ($m) {return $m['modo'] === 'extra';}) ? 'extra' : 'incluido';

        if ($modo === 'extra') {
            // EXTRA: normalmente ya viene 1 item por masaje (por persona)
            // Persona la asignamos correlativa (1..n) según el orden recibido.
            $persona = 1;

            // Pre-cargar nombres TipoMasaje por slug para evitar N queries
            $slugs         = collect($masajesNorm)->pluck('tipo_slug')->filter()->unique()->values()->all();
            $mapTipoNombre = $slugs
                ? TipoMasaje::whereIn('slug', $slugs)->pluck('nombre', 'slug')->toArray()
                : [];

            // Pre-cargar duraciones por precio_id (para tiempo_extra)
            $precioIds   = collect($masajesNorm)->pluck('precio_id')->filter()->unique()->values()->all();
            $mapDuracion = $precioIds
                ? PrecioTipoMasaje::whereIn('id', $precioIds)->pluck('duracion_minutos', 'id')->toArray()
                : [];

            foreach ($masajesNorm as $m) {
                if (empty($m['horario_masaje'])) {
                    continue;
                }

                if ($persona > $personasMasaje) {
                    break;
                }

                $tipoSlug = $m['tipo_slug'] ?? null;
                $precioId = $m['precio_id'] ?? null;
                $duracion = $precioId ? (int) ($mapDuracion[$precioId] ?? 0) : 0;

                Masaje::create([
                    'horario_masaje'  => Carbon::createFromFormat('H:i', $m['horario_masaje']),
                    'tipo_masaje'     => $tipoSlug ? ($mapTipoNombre[$tipoSlug] ?? null) : null,
                    'id_lugar_masaje' => (int) ($m['id_lugar_masaje'] ?? 1),
                    'persona'         => $persona,
                    'tiempo_extra'    => ($duracion !== 30 && $duracion > 0) ? true : false,
                    'id_reserva'      => $reserva->id,
                    'user_id'         => null,
                ]);

                $persona++;
            }

            // Si por algún motivo vinieron menos items que personasMasaje, crea placeholders restantes
            for (; $persona <= $personasMasaje; $persona++) {
                Masaje::create([
                    'horario_masaje'  => null,
                    'tipo_masaje'     => null,
                    'id_lugar_masaje' => 1,
                    'persona'         => $persona,
                    'tiempo_extra'    => null,
                    'id_reserva'      => $reserva->id,
                    'user_id'         => null,
                ]);
            }

            return;
        }

        // INCLUIDOS: tu lógica original: slots con hasta 2 personas por horario
        $contadorPersonas      = 1;
        $maxPersonasPorHorario = 2;

        foreach ($masajesNorm as $slot) {
            if (empty($slot['horario_masaje'])) {
                continue;
            }

            for ($i = 1; $i <= $maxPersonasPorHorario; $i++) {
                if ($contadorPersonas > $personasMasaje) {
                    break;
                }

                Masaje::create([
                    'horario_masaje'  => Carbon::createFromFormat('H:i', $slot['horario_masaje']),
                    'tipo_masaje'     => $slot['tipo_masaje'] ?? 'Relajación',
                    'id_lugar_masaje' => (int) ($slot['id_lugar_masaje'] ?? 1),
                    'persona'         => $contadorPersonas,
                    'tiempo_extra'    => (int) ($slot['tiempo_extra'] ?? 0) ? true : false,
                    'id_reserva'      => $reserva->id,
                    'user_id'         => null,
                ]);

                $contadorPersonas++;
            }
        }

        // Si faltaron masajes por asignar (pocos slots), completa placeholders
        for (; $contadorPersonas <= $personasMasaje; $contadorPersonas++) {
            Masaje::create([
                'horario_masaje'  => null,
                'tipo_masaje'     => null,
                'id_lugar_masaje' => 1,
                'persona'         => $contadorPersonas,
                'tiempo_extra'    => null,
                'id_reserva'      => $reserva->id,
                'user_id'         => null,
            ]);
        }
    }

    private function crearMenusSiCorresponde($request, Reserva $reserva): void
    {
        // Menús deben ser por asistentes reales, NO por cantidad de masajes extra
        $cantidadNecesaria = (int) $reserva->cantidad_personas;

        // Si ya existen, no duplicar
        $menusExistentes = Menu::where('id_reserva', $reserva->id)->count();
        if ($menusExistentes > 0) {
            return;
        }

        $menusPayload = $request->input('menus', []);
        if (! is_array($menusPayload)) {
            $menusPayload = [];
        }

        // Crea los enviados
        $creados = 0;
        foreach ($menusPayload as $menu) {
            if (! is_array($menu)) {
                continue;
            }

            Menu::create([
                'id_reserva'                 => $reserva->id,
                'id_producto_entrada'        => $menu['id_producto_entrada'] ?? null,
                'id_producto_fondo'          => $menu['id_producto_fondo'] ?? null,
                'id_producto_acompanamiento' => $menu['id_producto_acompanamiento'] ?? null,
                'alergias'                   => $menu['alergias'] ?? null,
                'observacion'                => $menu['observacion'] ?? null,
            ]);
            $creados++;
        }

        // Completa faltantes con vacíos
        $faltantes = max(0, $cantidadNecesaria - $creados);
        for ($i = 0; $i < $faltantes; $i++) {
            Menu::create([
                'id_reserva'                 => $reserva->id,
                'id_producto_entrada'        => null,
                'id_producto_fondo'          => null,
                'id_producto_acompanamiento' => null,
                'alergias'                   => null,
                'observacion'                => null,
            ]);
        }
    }

    private function procesarCobroMasajesExtra($request, Reserva $reserva): void
    {
        // Detecta extras: masajes[] con precio_id o singular masaje[precio_id]
        $masajesNorm = $this->normalizarMasajes($request);
        $soloExtras  = collect($masajesNorm)->filter(function ($m) {return $m['modo'] === 'extra' && ! empty($m['precio_id']);});

        if ($soloExtras->isEmpty()) {
            // No tocar detalle_extra si no hay precio_id
            return;
        }

        $ventaId = optional($reserva->venta)->id;
        if (! $ventaId) {
            throw new \Exception('La reserva no tiene venta asociada.');
        }

        $consumo = Consumo::where('id_venta', $ventaId)->first();
        if (! $consumo) {
            $consumo = Consumo::create([
                'id_venta'      => $ventaId,
                'subtotal'      => 0,
                'total_consumo' => 0,
            ]);
        }

        $idServicioMasaje = Servicio::where('slug', 'masaje')
            ->orWhereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])
            ->value('id');

        if (! $idServicioMasaje) {
            throw new \Exception('No se encontró el servicio extra "Masaje".');
        }

        $antes = (int) DetalleServiciosExtra::where('id_consumo', $consumo->id)
            ->where('id_servicio_extra', $idServicioMasaje)
            ->sum('subtotal');

        // Agrupar por precio_id
        $grupos = $soloExtras->groupBy('precio_id');

        // Precarga precios en 1 query
        $precioIds = $grupos->keys()->map(function ($x) {return (int) $x;})->values()->all();

        $precios = PrecioTipoMasaje::whereIn('id', $precioIds)
            ->get(['id', 'precio_unitario', 'precio_pareja'])
            ->keyBy('id');

        // Limpia placeholder null (si existe) para que no sume
        // DetalleServiciosExtra::where('id_consumo', $consumo->id)
        //     ->where('id_servicio_extra', $idServicioMasaje)
        //     ->whereNull('id_precio_tipo_masaje')
        //     ->lockForUpdate()
        //     ->update(['cantidad_servicio' => 0, 'subtotal' => 0]);

        DetalleServiciosExtra::where('id_consumo', $consumo->id)
            ->where('id_servicio_extra', $idServicioMasaje)
            ->whereNull('id_precio_tipo_masaje')
            ->lockForUpdate()
            ->delete();

        foreach ($grupos as $precioId => $items) {

            $precioId = (int) $precioId;
            $cantidad = (int) $items->count();

            $precio = $precios->get($precioId);
            if (! $precio) {
                throw ValidationException::withMessages([
                    'masajes' => "El precio seleccionado ($precioId) no existe.",
                ]);
            }

            $unit = (int) $precio->precio_unitario;
            $pair = is_null($precio->precio_pareja) ? null : (int) $precio->precio_pareja;

            if ($pair) {
                $pares    = intdiv($cantidad, 2);
                $impar    = $cantidad % 2;
                $subtotal = ($pares * $pair) + ($impar * $unit);
            } else {
                $subtotal = $cantidad * $unit;
            }

            // Create/update una línea por precio_id
            $detalle = DetalleServiciosExtra::where('id_consumo', $consumo->id)
                ->where('id_servicio_extra', $idServicioMasaje)
                ->where('id_precio_tipo_masaje', $precioId)
                ->lockForUpdate()
                ->first();

            if (! $detalle) {
                DetalleServiciosExtra::create([
                    'id_consumo'            => $consumo->id,
                    'id_servicio_extra'     => $idServicioMasaje,
                    'cantidad_servicio'     => $cantidad,
                    'id_precio_tipo_masaje' => $precioId,
                    'subtotal'              => $subtotal,
                ]);
            } else {
                $detalle->cantidad_servicio = $cantidad;
                $detalle->subtotal          = $subtotal;
                $detalle->save();
            }
        }

        // $this->recalcularTotalesConsumo($consumo->id);

        $despues = (int) DetalleServiciosExtra::where('id_consumo', $consumo->id)
            ->where('id_servicio_extra', $idServicioMasaje)
            ->sum('subtotal');

        $delta = $despues - $antes;

        $this->recalcularTotalesConsumo($consumo->id, $delta);

    }

    private function recalcularTotalesConsumo(int $consumoId, int $delta): void
    {
        $consumo = Consumo::find($consumoId);
        if (! $consumo) {
            return;
        }

        if ($delta === 0) {
            return;
        }

        $consumo->subtotal      = (int) $consumo->subtotal + $delta;
        $consumo->total_consumo = (int) $consumo->total_consumo + $delta;
        $consumo->save();
    }

}
