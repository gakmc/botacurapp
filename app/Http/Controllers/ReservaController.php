<?php
namespace App\Http\Controllers;

use App\Cliente;
use App\Consumo;
use App\DetalleServiciosExtra;
use App\Http\Requests\Reserva\StoreRequest;
use App\Http\Requests\Reserva\UpdateRequest;
use App\Programa;
use App\Reagendamiento;
use App\Reserva;
use App\Servicio;
use App\TipoTransaccion;
use App\User;
use App\Venta;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
// use PDF;
use Barryvdh\DomPDF\Facade\Pdf;
use RealRashid\SweetAlert\Facades\Alert;

class ReservaController extends Controller
{

    public function index(Request $request)
    {
        Carbon::setLocale('es');
        $alternativeView = $request->query('alternative', false);
        $fechaActual     = Carbon::now()->startOfDay();

        if (!$alternativeView) {
            $reservas = Reserva::where('fecha_visita', '>=', Carbon::now()->startOfDay())
                ->with([
                    'cliente',
                    'visitas.ubicacion',
                    'visitas.masajes',
                    'programa',
                    'venta',
                ])
                ->orderBy('fecha_visita', 'asc')
                ->get();

        } else {

            $reservas = Reserva::where('fecha_visita', '>=', Carbon::now()->startOfDay())
                ->with([
                    'cliente',
                    'visitas.ubicacion',
                    'visitas.masajes',
                    'programa',
                    'venta',
                ])
                ->select('*')
                ->selectSub(
                    DB::table('visitas')
                        ->whereColumn('visitas.id_reserva', 'reservas.id')
                        ->orderBy('id_ubicacion', 'asc')
                        ->limit(1)
                        ->select('id_ubicacion'),
                    'first_id_ubicacion'
                )
                ->orderBy('fecha_visita', 'asc')
                ->orderBy('first_id_ubicacion', 'asc')
                // ->orderBy('fecha_visita', 'asc')
                // ->orderBy(function ($query) {
                //     $query->select('id_ubicacion')
                //           ->from('visitas')
                //           ->whereColumn('visitas.id_reserva', 'reservas.id')
                //           ->orderBy('id_ubicacion', 'asc');
                // })
                ->get();

        }

        /* Para dispositivos moviles */
        $reservasMoviles = Reserva::where('fecha_visita', '>=', Carbon::now()->startOfDay())
        ->with([
            'cliente',
            'visitas.ubicacion',
            'visitas.masajes',
            'programa',
            'venta',
        ])
        ->select('*')
        ->selectSub(
            DB::table('visitas')
                ->whereColumn('visitas.id_reserva', 'reservas.id')
                ->orderBy('id_ubicacion', 'asc')
                ->limit(1)
                ->select('id_ubicacion'),
            'first_id_ubicacion'
        )
        ->orderBy('fecha_visita', 'asc')
        ->orderBy('first_id_ubicacion', 'asc')
        // ->orderBy('fecha_visita', 'asc')
        // ->orderBy(function ($query) {
        //     $query->select('id_ubicacion')
        //           ->from('visitas')
        //           ->whereColumn('visitas.id_reserva', 'reservas.id')
        //           ->orderBy('id_ubicacion', 'asc');
        // })
        ->get();

        //     dd(
        //     Reserva::where('fecha_visita', '>=', Carbon::now()->startOfDay())
        //     ->with([
        //         'cliente',
        //         'visitas.ubicacion',
        //         'visitas.masajes',
        //         'programa',
        //         'venta',
        //     ])    ->join('visitas', 'visitas.id_reserva', '=', 'reservas.id')
        //     ->orderBy('reservas.fecha_visita', 'asc')
        //     ->orderBy('visitas.id_ubicacion', 'asc')
        //     ->select('reservas.*') // Para evitar columnas repetidas al hacer join
        //     ->get()
        // );
        
        $reservasMoviles->load(['visitas.masajes', 'visitas.ubicacion']);
        $reservasDia = $reservasMoviles->groupBy(function ($reservamovil) {
            return Carbon::parse($reservamovil->fecha_visita)->format('d-m-Y');
        });

        $porPagina           = 1;
        $paginaActual       = LengthAwarePaginator::resolveCurrentPage();
        $itemsActuales      = $reservasDia->slice(($paginaActual - 1) * $porPagina, $porPagina)->all();
        $reservasMovilesPaginadas = new LengthAwarePaginator($itemsActuales, $reservasDia->count(), $porPagina, $paginaActual);
        $reservasMovilesPaginadas->setPath(request()->url());
        /* Fin dispositivos moviles */



        $reservas->load(['visitas.masajes', 'visitas.ubicacion']);


        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        $perPage           = 1;
        $currentPage       = LengthAwarePaginator::resolveCurrentPage();
        $currentItems      = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage);
        $reservasPaginadas->setPath(request()->url());

        // dd($reservas);

        return view('themes.backoffice.pages.reserva.index', compact('reservasPaginadas', 'alternativeView', 'reservasMovilesPaginadas'));
    }

    public function indexOld(Request $request)
    {
        Carbon::setLocale('es');
        $alternativeView = $request->query('alternative', false);
        $fechaActual     = Carbon::now()->startOfDay();

        if (! $alternativeView) {
            $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
                ->join('clientes as c', 'reservas.cliente_id', '=', 'c.id')
                ->join('visitas as v', 'v.id_reserva', '=', 'reservas.id')
                ->leftjoin('masajes as m', 'm.id_visita', '=', 'v.id')
                ->join('ubicaciones as u', 'v.id_ubicacion', '=', 'u.id')
                ->join('programas as p', 'reservas.id_programa', '=', 'p.id')
                ->join('ventas as vt', 'reservas.id', '=', 'vt.id_reserva')
                ->select(
                    'reservas.*',
                    'v.horario_sauna',
                    'v.horario_tinaja',
                    'm.horario_masaje',
                    'c.nombre_cliente',
                    'u.nombre as ubicacion',
                    'p.nombre_programa as programa_nombre',
                    'vt.total_pagar as venta_total'
                )
                ->orderBy('reservas.fecha_visita', 'asc')
                ->orderBy('v.horario_sauna', 'asc')
                ->get();

        } else {

            $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
                ->join('clientes as c', 'reservas.cliente_id', '=', 'c.id')
                ->join('visitas as v', 'v.id_reserva', '=', 'reservas.id')
                ->leftjoin('masajes as m', 'm.id_visita', '=', 'v.id')
                ->join('ubicaciones as u', 'v.id_ubicacion', '=', 'u.id')
                ->join('programas as p', 'reservas.id_programa', '=', 'p.id')
                ->join('ventas as vt', 'reservas.id', '=', 'vt.id_reserva')
                ->select(
                    'reservas.*',
                    'v.horario_sauna',
                    'v.horario_tinaja',
                    'm.horario_masaje',
                    'c.nombre_cliente',
                    'u.nombre as ubicacion',
                    'p.nombre_programa as programa_nombre',
                    'vt.total_pagar as venta_total'
                )
                ->orderBy('reservas.fecha_visita', 'asc')
                ->orderBy('v.horario_sauna', 'asc')
                ->get();

        }

        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        $perPage           = 1;
        $currentPage       = LengthAwarePaginator::resolveCurrentPage();
        $currentItems      = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage);
        $reservasPaginadas->setPath(request()->url());

        // dd($reservas);

        return view('themes.backoffice.pages.reserva.index', compact('reservasPaginadas', 'alternativeView'));
    }

    public function create($cliente)
    {
        $this->authorize('create', User::class);
        $cliente   = Cliente::findOrFail($cliente);
        $programas = Programa::with('servicios')->get();
        $tipos     = TipoTransaccion::all();

        return view('themes.backoffice.pages.reserva.create', [
            'cliente'   => $cliente,
            'programas' => $programas,
            'tipos'     => $tipos,
        ]);
    }

    public function verificarUbicaciones(Request $request)
    {
        $fechaSeleccionada = $request->input('fecha');

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

        return response()->json($ubicaciones);
    }

    public function store(StoreRequest $request, Reserva $reserva)
    {
        $masajesExtra         = null;
        $almuerzosExtra       = null;
        $cantidadMasajesExtra = null;

        // Verificar si el programa seleccionado incluye un masaje
        $programa = Programa::find($request->id_programa);

        // Buscar si el programa tiene un servicio de masaje
        $incluyeMasaje = $programa->servicios()->whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->exists();

        try {

            // Iniciar la transacción
            DB::transaction(function () use ($request, &$reserva, $incluyeMasaje, &$masajesExtra, &$almuerzosExtra) {

                // Asignar el id del cliente con la reserva
                if ($request->has('cliente_id')) {
                    $cliente = $request->cliente_id;
                    $request->merge(['cliente_id' => $cliente]);
                }

                // Asignar el user_id del usuario autenticado
                $user_id = auth()->id();
                $request->merge(['user_id' => $user_id]);

                // Crear la reserva
                $reserva = Reserva::create($request->all());

                // Asignar cantidad_personas a cantidad_masajes solo si el programa incluye masaje
                if ($incluyeMasaje) {
                    $reserva->update(['cantidad_masajes' => $reserva->cantidad_personas]);
                } else {
                    // Dejarlo nulo explícitamente si no hay masaje
                    $reserva->update(['cantidad_masajes' => null]);
                }

                // Generar url para almacenar imagen
                $url_abono = null;
                $filename  = null;

                if ($request->hasFile('imagen_abono')) {
                    $abono     = $request->file('imagen_abono');
                    $filename  = time() . '-' . $abono->getClientOriginalName();
                    $url_abono = 'temp/' . $filename; // Almacenamiento temporal
                    Storage::disk('imagen_abono')->put($url_abono, File::get($abono));
                }

                // Crear la venta relacionada con la reserva
                $venta = Venta::create([
                    'id_reserva'                => $reserva->id,
                    'abono_programa'            => $request->abono_programa,
                    'imagen_abono'              => $url_abono,
                    'id_tipo_transaccion_abono' => $request->tipo_transaccion,
                    'total_pagar'               => $request->total_pagar,
                ]);

                // Si la imagen fue almacenada temporalmente, moverla a su ubicación final
                if ($filename) {
                    $finalPath = '/' . $filename;
                    Storage::disk('imagen_abono')->move('temp/' . $filename, $finalPath);
                    $venta->update(['imagen_abono' => $finalPath]);
                }

                $consumo = null;

                if ($request->filled('cantidad_masajes_extra')) {

                    $masajesExtra = $request->filled('cantidad_masajes_extra');

                    $consumo = Consumo::create([
                        'id_venta'      => $venta->id,
                        'subtotal'      => 0,
                        'total_consumo' => 0,
                    ]);

                    $servicioMasaje = Servicio::whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->first();

                    if ($servicioMasaje) {
                        $subtotal             = 0;
                        $cantidadMasajesExtra = intval($request->input('cantidad_masajes_extra'));
                        $subtotalMasajes      = $servicioMasaje->valor_servicio * $cantidadMasajesExtra;
                        $subtotal             = $subtotalMasajes;

                        // Crear el detalle del servicio extra
                        DetalleServiciosExtra::create([
                            'cantidad_servicio' => $cantidadMasajesExtra,
                            'subtotal'          => $subtotalMasajes,
                            'id_consumo'        => $consumo->id,
                            'id_servicio_extra' => $servicioMasaje->id,
                        ]);

                        $consumo->subtotal += $subtotal;
                        $consumo->total_consumo += $subtotal;
                        $consumo->save();
                    }

                }

                if ($request->filled('agregar_almuerzos')) {

                    $almuerzosExtra = $request->filled('agregar_almuerzos');

                    if (! $consumo) {
                        $consumo = Consumo::create([
                            'id_venta'      => $venta->id,
                            'subtotal'      => 0,
                            'total_consumo' => 0,
                        ]);
                    }

                    $servicioAlmuerzo = Servicio::whereIn('nombre_servicio', ['Almuerzo', 'Almuerzos', 'almuerzo', 'almuerzos'])->first();

                    if ($servicioAlmuerzo) {
                        $subtotal               = 0;
                        $cantidadAlmuerzosExtra = intval($request->input('cantidad_personas'));
                        $subtotalAlmuerzos      = $servicioAlmuerzo->valor_servicio * $cantidadAlmuerzosExtra;
                        $subtotal               = $subtotalAlmuerzos;

                        // Crear el detalle del servicio extra
                        DetalleServiciosExtra::create([
                            'cantidad_servicio' => $cantidadAlmuerzosExtra,
                            'subtotal'          => $subtotalAlmuerzos,
                            'id_consumo'        => $consumo->id,
                            'id_servicio_extra' => $servicioAlmuerzo->id,
                        ]);

                        $consumo->subtotal += $subtotal;
                        $consumo->total_consumo += $subtotal;
                        $consumo->save();
                    }

                }
            });

            // Mostrar alerta de éxito
            // Alert::success('Éxito', 'Reserva realizada con éxito', 'Confirmar')->showConfirmButton();

            session()->put([
                'masajesExtra'         => $masajesExtra,
                'almuerzosExtra'       => $almuerzosExtra,
                'cantidadMasajesExtra' => $cantidadMasajesExtra,
            ]);

            // Redirigir fuera de la transacción
            return redirect()->route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id])->with('success','Reserva realizada con éxito');

        } catch (\Error $e) {
            // Alert::error('Falló', 'Error: ' . $e, 'Confirmar')->showConfirmButton();
            return redirect()->back()->with('error','Error: ' . $e)->withInput();
        }

    }

    public function show(Reserva $reserva)
    {
        $reserva->load('venta.consumos.detallesConsumos.producto', 'venta.consumos.detalleServiciosExtra.servicio', 'visitas.menus', 'visitas.menus.productoEntrada', 'visitas.menus.productoFondo', 'visitas.menus.productoacompanamiento', 'visitas.masajes');

        $servicios = Servicio::all();
        $visitas = $reserva->visitas;
        $masajes = null;


        foreach ($visitas as $visita) {
            if ($visita->masajes->isNotEmpty()) {
                
                $masajes = $visita->masajes;
            }
        }


        return view('themes.backoffice.pages.reserva.show', [
            'reserva'   => $reserva,
            'servicios' => $servicios,
            'visitas' => $visitas,
            'masajes' => $masajes
        ]);
    }

    public function showConsumoImage($id)
    {
        $reserva     = Reserva::findOrFail($id);
        $pagoConsumo = null;

        foreach ($reserva->venta->consumos as $consumo) {
            if ($consumo->pagosConsumos->where('id_consumo', $consumo->id)) {
                foreach ($consumo->pagosConsumos as $pago) {
                    $pagoConsumo = $pago;
                }
            }
        }

        // Verificar si el archivo de abono existe
        if (Storage::disk('imagen_consumo')->exists($pagoConsumo->imagen_transaccion)) {
            $file     = Storage::disk('imagen_consumo')->get($pagoConsumo->imagen_transaccion);
            $mimeType = Storage::disk('imagen_consumo')->mimeType($pagoConsumo->imagen_transaccion);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return abort(404, 'Imagen de abono no encontrada');
    }

    public function showAbonoImage($id)
    {
        $reserva = Reserva::findOrFail($id);

        // Verificar si el archivo de abono existe
        if (Storage::disk('imagen_abono')->exists($reserva->venta->imagen_abono)) {
            $file     = Storage::disk('imagen_abono')->get($reserva->venta->imagen_abono);
            $mimeType = Storage::disk('imagen_abono')->mimeType($reserva->venta->imagen_abono);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return abort(404, 'Imagen de abono no encontrada');
    }

    public function showDiferenciaImage($id)
    {
        $reserva = Reserva::findOrFail($id);

        // Verificar si el archivo de diferencia existe
        if (Storage::disk('imagen_diferencia')->exists($reserva->venta->imagen_diferencia)) {
            $file     = Storage::disk('imagen_diferencia')->get($reserva->venta->imagen_diferencia);
            $mimeType = Storage::disk('imagen_diferencia')->mimeType($reserva->venta->imagen_diferencia);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        // return abort(404, 'Imagen de diferencia no encontrada');
        return redirect('https://placehold.co/200x300');
    }

    public function edit(Reserva $reserva)
    {
        // $this->authorize('create', User::class);
        $cantidadExtraMasaje = null;
        $ventaId             = $reserva->venta->id;
        $consumo             = Consumo::where('id_venta', '=', $ventaId)->first();
        if (isset($consumo)) {
            $detalleServExtra    = DetalleServiciosExtra::where('id_consumo', '=', $consumo->id)->first();
            if (isset($detalleServExtra)) {
                $cantidadExtraMasaje = $detalleServExtra->cantidad_servicio;
            }
        }
        $cliente   = $reserva->cliente;
        $venta     = $reserva->venta;
        $visita    = $reserva->visitas->first();
        $programas = Programa::with('servicios')->get();
        $tipos     = TipoTransaccion::all();
        // dd(!$reserva->programa->servicios->contains('nombre_servicio', 'Masaje') && $visita->horario_masaje);
        return view('themes.backoffice.pages.reserva.edit', [
            'reserva'        => $reserva,
            'venta'          => $venta,
            'cliente'        => $cliente,
            'programas'      => $programas,
            'tipos'          => $tipos,
            'visita'         => $visita,
            'cantidadMasaje' => $cantidadExtraMasaje,
        ]);
    }

    public function update(UpdateRequest $request, Reserva $reserva)
    {
        $masajesExtra   = null;
        $almuerzosExtra = null;
        $data           = $request->all();

        $original = DB::table('reservas')
            ->join('ventas as v', 'reservas.id', '=', 'v.id_reserva')
            ->where('reservas.id', $reserva->id)
            ->select('reservas.*', 'v.abono_programa', 'v.id_tipo_transaccion_abono as tipo_transaccion', 'v.total_pagar')
            ->first();

        $originalArray = (array) $original; // Convertir objeto en arreglo

        // Parsear y formatear la fecha_visita del original
        if (! empty($originalArray['fecha_visita'])) {
            $originalArray['fecha_visita'] = Carbon::parse($originalArray['fecha_visita'])->format('d-m-Y'); // Ajusta el formato según sea necesario
        }

        $changes = [];

        foreach ($data as $key => $value) {
            // Comprobar si el valor existe en los datos originales y si es diferente
            if (array_key_exists($key, $originalArray) && $originalArray[$key] != $value) {
                $changes[$key] = [
                    'original' => $originalArray[$key],
                    'new'      => $value,
                ];
            }
        }

        // Verificar si hubo cambios
        if (! $changes) {
            // Si no hay cambios, redirigir a otra ruta
            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id])->with('info', 'No se realizaron cambios en la reserva.');
        }

        // Si hay cambios, continuar con la lógica normal
        try {
            DB::transaction(function () use ($request, &$reserva, $originalArray, &$masajesExtra, &$almuerzosExtra) {

                // dd($originalArray['fecha_visita'] ,$request->input('fecha_visita'));
                if ($originalArray['fecha_visita'] !== $request->input('fecha_visita')) {
                    $nuevaFecha = Carbon::createFromFormat('d-m-Y', $request->input('fecha_visita'))->format('Y-m-d');
                    // Guardar la fecha original de la reserva en el reagendamiento
                    $reagendamiento = Reagendamiento::create([
                        'fecha_original' => Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d'),
                        'nueva_fecha'    => $nuevaFecha,
                        'id_reserva'     => $reserva->id,
                    ]);
                    // Actualizar la reserva con la nueva fecha de visita
                    $reserva->fecha_visita = $nuevaFecha;
                };
                // Guardar los cambios en la reserva
                $reserva->save();

                // Verificar si el programa incluye masaje
                $programa      = Programa::find($request->id_programa);
                $incluyeMasaje = $programa->servicios()
                    ->whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])
                    ->exists();

                // Actualizar cantidad_masajes si el programa incluye masaje
                if ($incluyeMasaje) {
                    $reserva->update(['cantidad_masajes' => $reserva->cantidad_personas]);
                } else {
                    $reserva->update(['cantidad_masajes' => null]);
                }

                // Actualizar la venta relacionada con la reserva
                $venta = $reserva->venta ?? new Venta();

                // Manejo de la imagen de abono
                $url_abono = null;
                if ($request->hasFile('imagen_abono_boton')) {
                    if (! empty($venta->imagen_abono) && Storage::disk('imagen_abono')->exists($venta->imagen_abono)) {
                        Storage::disk('imagen_abono')->delete($venta->imagen_abono);
                    }
                    $abono     = $request->file('imagen_abono_boton');
                    $filename  = time() . '-' . $abono->getClientOriginalName();
                    $url_abono = 'temp/' . $filename;
                    Storage::disk('imagen_abono')->put($url_abono, File::get($abono));
                }

                $venta->fill([
                    'abono_programa'            => $request->abono_programa,
                    'imagen_abono'              => $url_abono ?? $venta->imagen_abono,
                    'id_tipo_transaccion_abono' => $request->tipo_transaccion,
                    'total_pagar'               => $request->total_pagar,
                ])->save();

                // Mover la imagen a su ubicación final, si es necesario
                if ($url_abono) {
                    $finalPath = '/' . $filename;
                    Storage::disk('imagen_abono')->move('temp/' . $filename, $finalPath);
                    $venta->update(['imagen_abono' => $finalPath]);
                }

                // Manejar los servicios extra
                if ($request->filled('cantidad_masajes_extra')) {
                    $masajesExtra = $request->filled('cantidad_masajes_extra');
                    $this->manipularMasajesExtra($request, $venta);
                }

                if ($request->filled('agregar_almuerzos')) {
                    $almuerzosExtra = $request->filled('agregar_almuerzos');
                    $this->manipularAlmuerzosExtra($request, $venta);
                }
            });

            session()->put([
                'masajesExtra'   => $masajesExtra,
                'almuerzosExtra' => $almuerzosExtra,
            ]);

            return redirect()->route('backoffice.reserva.visitas.edit', ['reserva' => $reserva, 'visita' => $reserva->visitas->first()])->with('success', 'La reserva fue actualizada exitosamente.');
        } catch (\Error $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar la reserva. ' . $e);
        }
    }

    private function manipularMasajesExtra($request, $venta)
    {
        $servicioMasaje = Servicio::whereIn('nombre_servicio', ['Masaje', 'Masajes', 'masaje', 'masajes'])->first();
        $consumo        = Consumo::where('id_venta', '=', $venta->id)->first();

        if ($servicioMasaje) {
            $cantidadMasajesExtra = intval($request->input('cantidad_masajes_extra'));
            $subtotalMasajes      = $servicioMasaje->valor_servicio * $cantidadMasajesExtra;

            DetalleServiciosExtra::updateOrCreate(
                [
                    'id_consumo'        => $consumo->id,
                    'id_servicio_extra' => $servicioMasaje->id,
                ],
                [
                    'cantidad_servicio' => $cantidadMasajesExtra,
                    'subtotal'          => $subtotalMasajes,
                ]
            );
        }
    }

    private function manipularAlmuerzosExtra($request, $venta)
    {
        $servicioAlmuerzo = Servicio::whereIn('nombre_servicio', ['Almuerzo', 'Almuerzos', 'almuerzo', 'almuerzos'])->first();
        if ($servicioAlmuerzo) {
            $cantidadAlmuerzosExtra = intval($request->input('cantidad_personas'));
            $subtotalAlmuerzos      = $servicioAlmuerzo->valor_servicio * $cantidadAlmuerzosExtra;

            DetalleServiciosExtra::updateOrCreate(
                [
                    'id_consumo'        => $venta->consumo->id,
                    'id_servicio_extra' => $servicioAlmuerzo->id,
                ],
                [
                    'cantidad_servicio' => $cantidadAlmuerzosExtra,
                    'subtotal'          => $subtotalAlmuerzos,
                ]
            );
        }
    }

    public function destroy(Reserva $reserva)
    {
        //
    }

    public function generarPDF(Reserva $reserva)
    {
        $reserva->load('venta.consumos.detallesConsumos.producto', 'venta.consumos.detalleServiciosExtra.servicio', 'visitas.menus', 'visitas.menus.productoEntrada', 'visitas.menus.productoFondo', 'visitas.menus.productoacompanamiento');
        
        $total   = 0;
        $propina = 'No Aplica';
        $visita  = $reserva->visitas->first();
        $reserva->visitas->last()->load(['menus']);
        $menus           = $reserva->visitas->last()->menus;
        $consumos        = $reserva->venta->consumos;
        $idConsumo       = null;
        $cantidadPropina = null;
        // dd($menus);
        
        if ($consumos->isEmpty()) {
            $propina = 'No Aplica';
        } else {
            foreach ($consumos as $consumo) {
                $idConsumo = $consumo->id;
                foreach ($consumo->detallesConsumos as $detalles) {
                    if ($detalles->genera_propina) {
                        $total   = $consumo->total_consumo;
                        $propina = 'Si';
                    } else {
                        $total   = $consumo->subtotal;
                        $propina = 'No';
                    }
                }
            }

            $cantidadPropina = DB::table('propinas')
                ->where('id_consumo', '=', $idConsumo)
                ->first();

            if ($cantidadPropina) {

                $cantidadPropina = $cantidadPropina->cantidad;
            }

        }

        $saveName = str_replace(' ', '_', $reserva->cliente->nombre_cliente);

        $data = [
            'nombre'        => $reserva->cliente->nombre_cliente,
            'numero'        => $reserva->cliente->whatsapp_cliente,
            'observacion'   => $reserva->observacion ? $reserva->observacion : 'Sin Observaciones',
            'fecha_visita'  => $reserva->fecha_visita,
            'programa'      => $reserva->programa->nombre_programa,
            'personas'      => $reserva->cantidad_personas,
            'menus'         => $menus,
            'consumos'      => $consumos,
            'venta'         => $reserva->venta,
            'total'         => $total,
            'propina'       => $propina,
            'propinaPagada' => $cantidadPropina ? $cantidadPropina : 'No Aplica',
        ];

        // dd($data);
        $pdf = Pdf::loadView('pdf.venta.viewPDF', $data);
        // return $pdf->download('factura.pdf');
        return $pdf->stream('Detalle_Venta' . '_' . $saveName . '_' . $reserva->fecha_visita . '.pdf');

    }

    public function generarPDFConsumo(Reserva $reserva)
    {
        $reserva->load('venta.consumos.detallesConsumos.producto', 'venta.consumos.detalleServiciosExtra.servicio', 'visitas.menus', 'visitas.menus.productoEntrada', 'visitas.menus.productoFondo', 'visitas.menus.productoacompanamiento');

        $total   = 0;
        $propina = 'No Aplica';
        $visita  = $reserva->visitas->first();
        $visita->load(['menus']);
        $consumos        = $reserva->venta->consumos;
        $idConsumo       = null;
        $cantidadPropina = null;

        if ($consumos->isEmpty()) {
            $propina = 'No Aplica';
        } else {
            foreach ($consumos as $consumo) {
                $idConsumo = $consumo->id;
                foreach ($consumo->detallesConsumos as $detalles) {
                    if ($detalles->genera_propina) {
                        $total   = $consumo->total_consumo;
                        $propina = 'Si';
                    } else {
                        $total   = $consumo->subtotal;
                        $propina = 'No';
                    }
                }
            }

            $cantidadPropina = DB::table('propinas')
                ->where('id_consumo', '=', $idConsumo)
                ->first();

            if ($cantidadPropina) {

                $cantidadPropina = $cantidadPropina->cantidad;
            }

        }

        $saveName = str_replace(' ', '_', $reserva->cliente->nombre_cliente);

        $dataConsumo = [
            'nombre'        => $reserva->cliente->nombre_cliente,
            'numero'        => $reserva->cliente->whatsapp_cliente,
            'observacion'   => $reserva->observacion ? $reserva->observacion : 'Sin Observaciones',
            'fecha_visita'  => $reserva->fecha_visita,
            'programa'      => $reserva->programa->nombre_programa,
            'personas'      => $reserva->cantidad_personas,
            'consumos'      => $consumos,
            'venta'         => $reserva->venta,
            'total'         => $total,
            'propina'       => $propina,
            'propinaPagada' => $cantidadPropina ? $cantidadPropina : 'No Aplica',
        ];

        $pdf = PDF::loadView('pdf.consumo_separado.viewPDF', $dataConsumo);
        return $pdf->stream('Detalle_Consumo' . '_' . $saveName . '_' . $reserva->fecha_visita . '.pdf');
    }

    public function indexall()
    {
        Carbon::setLocale('es');

        // Vista anterior
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        $reservasPorMes = Reserva::with(['cliente', 'visitas', 'programa.servicios', 'venta'])
            ->orderBy('fecha_visita')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->fecha_visita)->format('Y-m');
            });

        return view('themes.backoffice.pages.reserva.index_all', compact('reservasPorMes'));
    }

    public function indexReserva()
    {
        Carbon::setLocale('es');
        $fechaActual     = Carbon::now()->startOfDay();

        $reservas = Reserva::where('fecha_visita', '>=', Carbon::now()->startOfDay())
        ->with([
            'cliente',
            'visitas',
            'visitas.masajes',
            'programa',
            'venta',
            'visitas.menus'
        ])
        ->orderBy('fecha_visita', 'asc')
        ->get();

        $reservas->load(['visitas.masajes', 'visitas.ubicacion', 'visitas.menus', 'cliente']);


        $reservasPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        $perPage           = 1;
        $currentPage       = LengthAwarePaginator::resolveCurrentPage();
        $currentItems      = $reservasPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $reservasPaginadas = new LengthAwarePaginator($currentItems, $reservasPorDia->count(), $perPage, $currentPage);
        $reservasPaginadas->setPath(request()->url());

        return view('themes.backoffice.pages.reserva.index_registro', [
            'reservasPaginadas' => $reservasPaginadas
        ]);
    }
}
