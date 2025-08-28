<?php
namespace App\Http\Controllers;

use App\CategoriaMasaje;
use App\Consumo;
use App\DetalleConsumo;
use App\DetalleServiciosExtra;
use App\Events\Consumos\NuevoConsumoAgregado;
use App\Masaje;
use App\Menu;
use App\PrecioTipoMasaje;
use App\Producto;
use App\Servicio;
use App\TipoProducto;
use App\Ubicacion;
use App\Venta;
use App\Visita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert;

class ConsumoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function service_create($venta)
    {
        $venta = Venta::findOrFail($venta);

        $servicios = Servicio::all();

        $catalogoMasajes = CategoriaMasaje::with([
            'tipos'         => function ($q) {$q->where('activo', 1)->orderBy('nombre');},
            'tipos.precios' => function ($q) {$q->orderBy('duracion_minutos');},
        ])->orderBy('nombre')->get();

        return view('themes.backoffice.pages.consumo.create_service', [
            'venta'           => $venta,
            'servicios'       => $servicios,
            'catalogoMasajes' => $catalogoMasajes,
        ]);
    }

    public function old_service_store(Request $request, Venta $venta)
    {
        $validarMasaje   = ['masajes', 'masaje'];
        $validarSauna    = ['saunas', 'sauna'];
        $validarTinaja   = ['tinajas', 'tinaja'];
        $validarAlmuerzo = ['almuerzos', 'almuerzo'];

        DB::transaction(function () use ($request, &$venta, $validarMasaje, $validarSauna, $validarTinaja, $validarAlmuerzo) {
            // Verificar si ya existe un consumo para esta venta
            $consumo = Consumo::where('id_venta', $request->id_venta)->first();

            // Si no existe, creamos el consumo con valores iniciales
            if (! $consumo) {
                $consumo = Consumo::create([
                    'id_venta'      => $request->id_venta,
                    'subtotal'      => 0,
                    'total_consumo' => 0,
                ]);
            }

            // Inicializar variables
            $totalSubtotal = 0;
            $nuevoSubtotal = 0;

            // Filtrar los productos del request con cantidad válida (mayor que 0)
            $serviciosValidos = array_filter($request->servicios, function ($servicio) {
                return isset($servicio['cantidad']) && $servicio['cantidad'] > 0;
            });

            $spaCantidadSauna  = 0;
            $spaCantidadTinaja = 0;

            // Recorrer los productos válidos y crear los detalles de consumo
            foreach ($serviciosValidos as $servicio_id => $servicio) {
                $tiempoExtra = isset($servicio['tiempo_extra']) ? true : false;

                $unidad   = $tiempoExtra ? (($servicio['precio'] - 1000) * 2) : $servicio['precio'];
                $subtotal = $unidad * $servicio['cantidad'];

                DetalleServiciosExtra::create([
                    'id_consumo'        => $consumo->id,
                    'id_servicio_extra' => $servicio_id,
                    'cantidad_servicio' => $servicio['cantidad'],
                    'subtotal'          => $subtotal,
                ]);

                // Sumar al subtotal del nuevo consumo
                $nuevoSubtotal += $subtotal;

                $nombreServicio = Servicio::findOrFail($servicio_id);

                if (in_array(strtolower($nombreServicio->nombre_servicio), $validarSauna)) {
                    $spaCantidadSauna += $servicio['cantidad'];
                }

                if (in_array(strtolower($nombreServicio->nombre_servicio), $validarTinaja)) {
                    $spaCantidadTinaja += $servicio['cantidad'];
                }

                // if (in_array(strtolower($nombreServicio->nombre_servicio), $validarMasaje)) {

                //     $tiempoExtraActual = ! empty($servicio['tiempo_extra_actual']); // aumentar tiempo a existentes
                //     $tiempoExtraNuevo  = ! empty($servicio['tiempo_extra']);        // crear nuevos 1 hr
                //     $cantidad          = max(1, (int) ($servicio['cantidad'] ?? 0));

                //     // Caso 1: No se selecciona nada -> crear 1 masaje de 30 min
                //     if (! $tiempoExtraActual && ! $tiempoExtraNuevo) {
                //         Masaje::create([
                //             'id_reserva'      => $venta->reserva->id,
                //             'horario_masaje'  => null,
                //             'tipo_masaje'     => null,
                //             'id_lugar_masaje' => 1,
                //             'persona'         => ($venta->reserva->next_persona),
                //             'tiempo_extra'    => false, // 30 min por defecto (sin extra)
                //             'user_id'         => null,
                //         ]);
                //     }

                //     // Caso 2: Aumentar tiempo a masajes actuales -> SOLO "cantidad" y marcar tiempo_extra=true
                //     if ($tiempoExtraActual) {
                //         $masajesAActualizar = $venta->reserva->masajes()
                //             ->where(function ($q) {
                //                 $q->whereNull('tiempo_extra')->orWhere('tiempo_extra', false);
                //             })
                //             ->orderBy('id') // elige los más antiguos primero (ajusta si prefieres otros)
                //             ->limit($cantidad)
                //             ->get();

                //         foreach ($masajesAActualizar as $m) {
                //             $m->tiempo_extra = true; // pasa a true
                //             $m->save();
                //         }
                //     }

                //     // Caso 3: Crear nuevos de 1 hora -> crear "cantidad" marcando tiempo_extra=true
                //     if ($tiempoExtraNuevo) {
                //         for ($i = 1; $i <= $cantidad; $i++) {
                //             Masaje::create([
                //                 'id_reserva'      => $venta->reserva->id,
                //                 'horario_masaje'  => null,
                //                 'tipo_masaje'     => null,
                //                 'id_lugar_masaje' => 1,
                //                 'persona'         => ($venta->reserva->masajes()->count() + $i),
                //                 'tiempo_extra'    => true, // identifica que es el "extra" (1 hr)
                //                 'user_id'         => null,
                //             ]);
                //         }
                //     }

                //     // for($i = 1; $i <= $servicio['cantidad']; $i++){
                //     //     $cantidadPersonas = isset($venta->reserva->cantidad_masajes) 
                //     //     ? $venta->reserva->cantidad_masajes+$i 
                //     //     : $venta->reserva->cantidad_personas+$i;

                //     //     Masaje::create([
                //     //         'id_reserva' => $venta->reserva->id,
                //     //         'horario_masaje' => null,
                //     //         'tipo_masaje' => null,
                //     //         'id_lugar_masaje' => null, 
                //     //         'persona' => $cantidadPersonas,
                //     //         'tiempo_extra' => $tiempoExtra,
                //     //         'user_id' => null,
                //     //     ]);
                //     // }

                // }

                if (in_array(strtolower($nombreServicio->nombre_servicio), $validarMasaje)) {

                    $tiempoExtraActual = ! empty($servicio['tiempo_extra_actual']); // subir a 1hr existentes
                    $tiempoExtraNuevo  = ! empty($servicio['tiempo_extra']);        // pedir “extras”
                    $cantidad          = max(1, (int) ($servicio['cantidad'] ?? 0));

                    $reserva   = $venta->reserva;
                    $reservaId = $reserva->id;

                    // Query base
                    $masajesQuery = $reserva->masajes()->orderBy('id');
                    $baseScope    = function () use ($masajesQuery) {
                        $q = clone $masajesQuery;
                        return $q->where(function ($q2) {
                            $q2->whereNull('tiempo_extra')->orWhere('tiempo_extra', false);
                        });
                    };

                    $crearBase = function (int $n) use ($reservaId, $venta) {
                        for ($i = 0; $i < $n; $i++) {
                            Masaje::create([
                                'id_reserva'      => $reservaId,
                                'horario_masaje'  => null,
                                'tipo_masaje'     => null,
                                'id_lugar_masaje' => 1,
                                'persona'         => ($venta->reserva->next_persona),
                                'tiempo_extra'    => false, // 30 min
                                'user_id'         => null,
                            ]);
                        }
                    };

                    $crearExtras = function (int $n) use ($reservaId, $venta) {
                        for ($i = 0; $i < $n; $i++) {
                            Masaje::create([
                                'id_reserva'      => $reservaId,
                                'horario_masaje'  => null,
                                'tipo_masaje'     => null,
                                'id_lugar_masaje' => 1,
                                'persona'         => ($venta->reserva->next_persona),
                                'tiempo_extra'    => true, // 1 hora
                                'user_id'         => null,
                            ]);
                        }
                    };

                    $subirAExtra = function (int $n) use ($baseScope) {
                        $pendientes = $baseScope()->limit($n)->get();
                        foreach ($pendientes as $m) {
                            $m->tiempo_extra = true;
                            $m->save();
                        }
                        return $pendientes->count();
                    };

                    // 1) Subir actuales hasta cubrir "cantidad"
                    $restantes = (int) ($servicio['cantidad'] ?? 0);
                    if ($tiempoExtraActual && $restantes > 0) {
                        $subidos   = $subirAExtra($restantes);
                        $restantes = max(0, $restantes - $subidos);
                    }

                    // 2) Completar faltantes:
                    //    - Si NO marcaron "nuevo", crear BASE (tiempo_extra=false)
                    //    - Si marcaron "nuevo", crear EXTRAS
                    if ($restantes > 0) {
                        if ($tiempoExtraNuevo) {
                            $crearExtras($restantes);
                        } else {
                            $crearBase($restantes); // << aquí se crea el 5º como base
                        }
                    }

                    // 3) Si no marcaron nada y no hay masajes, crea 1 base
                    if (! $tiempoExtraActual && ! $tiempoExtraNuevo && $masajesQuery->count() === 0) {
                        $crearBase(1);
                    }
                }

                if (in_array(strtolower($nombreServicio->nombre_servicio), $validarAlmuerzo)) {
                    $cantidad = (int) $servicio['cantidad'];

                    for ($i = 1; $i <= $cantidad; $i++) {
                        Menu::create([
                            'id_reserva'                 => $venta->reserva->id,
                            'id_producto_entrada'        => null,
                            'id_producto_fondo'          => null,
                            'id_producto_acompanamiento' => null,
                            'alergias'                   => null,
                            'observacion'                => null,
                        ]);
                    }
                }

            }

            $spaCombinados = min($spaCantidadSauna, $spaCantidadTinaja);

            if ($spaCombinados > 0) {
                for ($i = 1; $i <= $spaCombinados; $i++) {
                    Visita::create([
                        'horario_sauna'  => null,
                        'horario_tinaja' => null,
                        'trago_cortesia' => false,
                        'observacion'    => null,
                        'id_reserva'     => $venta->reserva->id,
                        'id_ubicacion'   => $venta->reserva->visitas->first()->id_ubicacion,
                    ]);
                }
            }

            $consumo->subtotal += $nuevoSubtotal;
            $consumo->total_consumo += $nuevoSubtotal;

            $consumo->save();

        });

        $venta = Venta::where('id', $request->id_venta)->first();

        Alert::success('Éxito', 'Servicio extra ingresado correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', $venta->reserva->id);
    }

    public function working_service_store(Request $request, Venta $venta)
    {
        dd($request->all());
        $validarMasaje   = ['masajes', 'masaje'];
        $validarSauna    = ['saunas', 'sauna'];
        $validarTinaja   = ['tinajas', 'tinaja'];
        $validarAlmuerzo = ['almuerzos', 'almuerzo'];

        DB::transaction(function () use ($request, &$venta, $validarMasaje, $validarSauna, $validarTinaja, $validarAlmuerzo) {
            // Verificar si ya existe un consumo para esta venta
            $consumo = Consumo::where('id_venta', $request->id_venta)->first();

            // Si no existe, creamos el consumo con valores iniciales
            if (! $consumo) {
                $consumo = Consumo::create([
                    'id_venta'      => $request->id_venta,
                    'subtotal'      => 0,
                    'total_consumo' => 0,
                ]);
            }

            // Inicializar variables
            $totalSubtotal = 0;
            $nuevoSubtotal = 0;

            // Filtrar los productos del request con cantidad válida (mayor que 0)
            $serviciosValidos = array_filter($request->servicios, function ($servicio) {
                return isset($servicio['cantidad']) && $servicio['cantidad'] > 0;
            });

            $spaCantidadSauna  = 0;
            $spaCantidadTinaja = 0;

            // Recorrer los productos válidos y crear los detalles de consumo
            foreach ($serviciosValidos as $servicio_id => $servicio) {
                $tiempoExtra = isset($servicio['tiempo_extra']) ? true : false;

                $unidad   = $tiempoExtra ? (($servicio['precio'] - 1000) * 2) : $servicio['precio'];
                $subtotal = $unidad * $servicio['cantidad'];

                DetalleServiciosExtra::create([
                    'id_consumo'        => $consumo->id,
                    'id_servicio_extra' => $servicio_id,
                    'cantidad_servicio' => $servicio['cantidad'],
                    'subtotal'          => $subtotal,
                ]);

                // Sumar al subtotal del nuevo consumo
                $nuevoSubtotal += $subtotal;

                $nombreServicio = Servicio::findOrFail($servicio_id);

                if (in_array(strtolower($nombreServicio->nombre_servicio), $validarSauna)) {
                    $spaCantidadSauna += $servicio['cantidad'];
                }

                if (in_array(strtolower($nombreServicio->nombre_servicio), $validarTinaja)) {
                    $spaCantidadTinaja += $servicio['cantidad'];
                }

                // if (in_array(strtolower($nombreServicio->nombre_servicio), $validarMasaje)) {

                //     $tiempoExtraActual = ! empty($servicio['tiempo_extra_actual']); // aumentar tiempo a existentes
                //     $tiempoExtraNuevo  = ! empty($servicio['tiempo_extra']);        // crear nuevos 1 hr
                //     $cantidad          = max(1, (int) ($servicio['cantidad'] ?? 0));

                //     // Caso 1: No se selecciona nada -> crear 1 masaje de 30 min
                //     if (! $tiempoExtraActual && ! $tiempoExtraNuevo) {
                //         Masaje::create([
                //             'id_reserva'      => $venta->reserva->id,
                //             'horario_masaje'  => null,
                //             'tipo_masaje'     => null,
                //             'id_lugar_masaje' => 1,
                //             'persona'         => ($venta->reserva->next_persona),
                //             'tiempo_extra'    => false, // 30 min por defecto (sin extra)
                //             'user_id'         => null,
                //         ]);
                //     }

                //     // Caso 2: Aumentar tiempo a masajes actuales -> SOLO "cantidad" y marcar tiempo_extra=true
                //     if ($tiempoExtraActual) {
                //         $masajesAActualizar = $venta->reserva->masajes()
                //             ->where(function ($q) {
                //                 $q->whereNull('tiempo_extra')->orWhere('tiempo_extra', false);
                //             })
                //             ->orderBy('id') // elige los más antiguos primero (ajusta si prefieres otros)
                //             ->limit($cantidad)
                //             ->get();

                //         foreach ($masajesAActualizar as $m) {
                //             $m->tiempo_extra = true; // pasa a true
                //             $m->save();
                //         }
                //     }

                //     // Caso 3: Crear nuevos de 1 hora -> crear "cantidad" marcando tiempo_extra=true
                //     if ($tiempoExtraNuevo) {
                //         for ($i = 1; $i <= $cantidad; $i++) {
                //             Masaje::create([
                //                 'id_reserva'      => $venta->reserva->id,
                //                 'horario_masaje'  => null,
                //                 'tipo_masaje'     => null,
                //                 'id_lugar_masaje' => 1,
                //                 'persona'         => ($venta->reserva->masajes()->count() + $i),
                //                 'tiempo_extra'    => true, // identifica que es el "extra" (1 hr)
                //                 'user_id'         => null,
                //             ]);
                //         }
                //     }

                //     // for($i = 1; $i <= $servicio['cantidad']; $i++){
                //     //     $cantidadPersonas = isset($venta->reserva->cantidad_masajes) 
                //     //     ? $venta->reserva->cantidad_masajes+$i 
                //     //     : $venta->reserva->cantidad_personas+$i;

                //     //     Masaje::create([
                //     //         'id_reserva' => $venta->reserva->id,
                //     //         'horario_masaje' => null,
                //     //         'tipo_masaje' => null,
                //     //         'id_lugar_masaje' => null, 
                //     //         'persona' => $cantidadPersonas,
                //     //         'tiempo_extra' => $tiempoExtra,
                //     //         'user_id' => null,
                //     //     ]);
                //     // }

                // }

                //ESTE ESTABA FUNCIONAL
                // if (in_array(strtolower($nombreServicio->nombre_servicio), $validarMasaje)) {

                //     $tiempoExtraActual = !empty($servicio['tiempo_extra_actual']); // subir a 1hr existentes
                //     $tiempoExtraNuevo  = !empty($servicio['tiempo_extra']);        // pedir “extras”
                //     $cantidad          = max(1, (int) ($servicio['cantidad'] ?? 0));

                //     $reserva   = $venta->reserva;
                //     $reservaId = $reserva->id;

                //     // Query base
                //     $masajesQuery = $reserva->masajes()->orderBy('id');
                //     $baseScope = function() use ($masajesQuery) {
                //         $q = clone $masajesQuery;
                //         return $q->where(function ($q2) {
                //             $q2->whereNull('tiempo_extra')->orWhere('tiempo_extra', false);
                //         });
                //     };

                //     $crearBase = function (int $n) use ($reservaId,  $venta) {
                //         for ($i = 0; $i < $n; $i++) {
                //             Masaje::create([
                //                 'id_reserva'      => $reservaId,
                //                 'horario_masaje'  => null,
                //                 'tipo_masaje'     => null,
                //                 'id_lugar_masaje' => 1,
                //                 'persona'         => ($venta->reserva->next_persona),
                //                 'tiempo_extra'    => false, // 30 min
                //                 'user_id'         => null,
                //             ]);
                //         }
                //     };

                //     $crearExtras = function (int $n) use ($reservaId,  $venta) {
                //         for ($i = 0; $i < $n; $i++) {
                //             Masaje::create([
                //                 'id_reserva'      => $reservaId,
                //                 'horario_masaje'  => null,
                //                 'tipo_masaje'     => null,
                //                 'id_lugar_masaje' => 1,
                //                 'persona'         => ($venta->reserva->next_persona),
                //                 'tiempo_extra'    => true, // 1 hora
                //                 'user_id'         => null,
                //             ]);
                //         }
                //     };

                //     $subirAExtra = function (int $n) use ($baseScope) {
                //         $pendientes = $baseScope()->limit($n)->get();
                //         foreach ($pendientes as $m) {
                //             $m->tiempo_extra = true;
                //             $m->save();
                //         }
                //         return $pendientes->count();
                //     };

                //     // 1) Subir actuales hasta cubrir "cantidad"
                //     $restantes = (int) ($servicio['cantidad'] ?? 0);
                //     if ($tiempoExtraActual && $restantes > 0) {
                //         $subidos   = $subirAExtra($restantes);
                //         $restantes = max(0, $restantes - $subidos);
                //     }

                //     // 2) Completar faltantes:
                //     //    - Si NO marcaron "nuevo", crear BASE (tiempo_extra=false)
                //     //    - Si marcaron "nuevo", crear EXTRAS
                //     if ($restantes > 0) {
                //         if ($tiempoExtraNuevo) {
                //             $crearExtras($restantes);
                //         } else {
                //             $crearBase($restantes);   // << aquí se crea el 5º como base
                //         }
                //     }

                //     // 3) Si no marcaron nada y no hay masajes, crea 1 base
                //     if (!$tiempoExtraActual && !$tiempoExtraNuevo && $masajesQuery->count() === 0) {
                //         $crearBase(1);
                //     }
                // }

                $servicioBD   = Servicio::findOrFail($servicio_id);
                $slugServicio = $servicioBD->slug; // p.ej. "masaje", "tinaja", "sauna"

                if ($slugServicio === 'masaje') {
                    $slugTipo = isset($servicio['slug_tipo_masaje']) ? strtolower($servicio['slug_tipo_masaje']) : '';
                    $duracion = isset($servicio['duracion']) ? (int) $servicio['duracion'] : 0;
                    $cantidad = isset($servicio['cantidad']) ? (int) $servicio['cantidad'] : 0;

                    $precio = PrecioTipoMasaje::with('tipo')
                        ->whereHas('tipo', function ($q) use ($slugTipo) {$q->where('slug', $slugTipo);})
                        ->where('duracion_minutos', $duracion)
                        ->first();

                    if (! $precio) {
                        throw ValidationException::withMessages(['servicios' => 'Tipo o duración de masaje inválida.']);
                    }

                    // 2x automático solo para Relajación 30/60 y si existe precio_pareja
                    $esRelajacion2x = ($precio->tipo->slug === 'relajacion') && in_array($duracion, [30, 60], true) && ! is_null($precio->precio_pareja);
                    $pares          = $esRelajacion2x ? intdiv($cantidad, 2) : 0;
                    $resto          = $cantidad - ($pares * 2);

                    $subtotal = ($pares * (int) $precio->precio_pareja) + ($resto * (int) $precio->precio_unitario);

                    DetalleServiciosExtra::create([
                        'id_consumo'            => $consumo->id,
                        'id_servicio_extra'     => $servicio_id, // apunta a "Masaje"
                        'id_precio_tipo_masaje' => $precio->id,  // referencia fina tipo+duración
                        'cantidad_servicio'     => $cantidad,
                        'subtotal'              => $subtotal,
                    ]);

                    // Crea/actualiza registros en `masajes` según duración:
                    // 30 => tiempo_extra=false; 60 => true; 45 => puedes dejar null/false según tu regla
                } else {
                    $cantidad = isset($servicio['cantidad']) ? (int) $servicio['cantidad'] : 0;
                    $unitario = (int) ($servicioBD->valor_servicio ?: 0); // ejemplo: Tinaja 10000, Sauna 7500
                    $subtotal = $unitario * $cantidad;

                    DetalleServiciosExtra::create([
                        'id_consumo'            => $consumo->id,
                        'id_servicio_extra'     => $servicio_id,
                        'id_precio_tipo_masaje' => null, // otros servicios: NULL
                        'cantidad_servicio'     => $cantidad,
                        'subtotal'              => $subtotal,
                    ]);
                }

                if (in_array(strtolower($nombreServicio->nombre_servicio), $validarAlmuerzo)) {
                    $cantidad = (int) $servicio['cantidad'];

                    for ($i = 1; $i <= $cantidad; $i++) {
                        Menu::create([
                            'id_reserva'                 => $venta->reserva->id,
                            'id_producto_entrada'        => null,
                            'id_producto_fondo'          => null,
                            'id_producto_acompanamiento' => null,
                            'alergias'                   => null,
                            'observacion'                => null,
                        ]);
                    }
                }

            }

            $spaCombinados = min($spaCantidadSauna, $spaCantidadTinaja);

            if ($spaCombinados > 0) {
                for ($i = 1; $i <= $spaCombinados; $i++) {
                    Visita::create([
                        'horario_sauna'  => null,
                        'horario_tinaja' => null,
                        'trago_cortesia' => false,
                        'observacion'    => null,
                        'id_reserva'     => $venta->reserva->id,
                        'id_ubicacion'   => $venta->reserva->visitas->first()->id_ubicacion,
                    ]);
                }
            }

            $consumo->subtotal += $nuevoSubtotal;
            $consumo->total_consumo += $nuevoSubtotal;

            $consumo->save();

        });

        $venta = Venta::where('id', $request->id_venta)->first();

        Alert::success('Éxito', 'Servicio extra ingresado correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', $venta->reserva->id);
    }

    public function service_store(Request $request, Venta $venta)
    {
        // Validación mínima genérica
        $request->validate([
            'id_venta'  => 'required|exists:ventas,id',
            'servicios' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request, &$venta) {

            // 1) Consumo (crea si no existe)
            $consumo = Consumo::firstOrCreate(
                ['id_venta' => $request->id_venta],
                ['subtotal' => 0, 'total_consumo' => 0]
            );

            $nuevoSubtotal = 0;
            $spaSauna      = 0;
            $spaTinaja     = 0;

            // 2) Filtrar items con cantidad > 0
            $serviciosValidos = array_filter($request->servicios, function ($srv) {
                return isset($srv['cantidad']) && (int) $srv['cantidad'] > 0;
            });

            // foreach ($serviciosValidos as $servicio_id => $srv) {

            //     $servicioBD   = Servicio::findOrFail($servicio_id);
            //     $slugServicio = $servicioBD->slug ?: str_slug($servicioBD->nombre_servicio);
            //     $cantidad     = (int) ($srv['cantidad'] ?? 0);

            //     if ($slugServicio === 'masaje') {
            //         // ===== MASAJES =====
            //         $slugTipo = strtolower($srv['slug_tipo_masaje'] ?? '');
            //         $duracion = (int) ($srv['duracion'] ?? 0);

            //         // Precio por (tipo, duración)
            //         $precio = PrecioTipoMasaje::with('tipo')
            //             ->whereHas('tipo', function($q) use ($slugTipo){ $q->where('slug',$slugTipo); })
            //             ->where('duracion_minutos', $duracion)
            //             ->first();

            //         if (!$precio) {
            //             throw ValidationException::withMessages([
            //                 'servicios' => 'Tipo o duración de masaje inválida.'
            //             ]);
            //         }

            //         // 2x automático para Relajación 30/60 si hay precio_pareja
            //         $isRelaj = ($precio->tipo->slug === 'relajacion') && in_array($duracion, array(30,60), true) && !is_null($precio->precio_pareja);
            //         $pares   = $isRelaj ? intdiv($cantidad, 2) : 0;
            //         $resto   = $cantidad - ($pares*2);

            //         $subtotal = $pares * (int)$precio->precio_pareja + $resto * (int)$precio->precio_unitario;

            //         // Detalle con referencia al catálogo
            //         DetalleServiciosExtra::create([
            //             'id_consumo'            => $consumo->id,
            //             'id_servicio_extra'     => $servicio_id,     // fila "Masaje" en servicios
            //             'id_precio_tipo_masaje' => $precio->id,      // (tipo, duración)
            //             'cantidad_servicio'     => $cantidad,
            //             'subtotal'              => $subtotal,
            //         ]);

            //         $nuevoSubtotal += $subtotal;

            //         // ===== Actualizar tabla masajes según opción =====
            //         $tiempoExtraActual = !empty($srv['tiempo_extra_actual']); // subir base->extra
            //         $tiempoExtraNuevo  = !empty($srv['tiempo_extra']);        // crear nuevos 60
            //         $reserva           = $venta->reserva;
            //         $reservaId         = $reserva->id;

            //         // Helpers
            //         $masajesBaseQuery = $reserva->masajes()->where(function($q){
            //             $q->whereNull('tiempo_extra')->orWhere('tiempo_extra', false);
            //         })->orderBy('id');

            //         $crearBase = function($n) use ($reservaId, $venta){
            //             for ($i=0; $i<$n; $i++) {
            //                 Masaje::create([
            //                     'id_reserva'      => $reservaId,
            //                     'horario_masaje'  => null,
            //                     'tipo_masaje'     => null,
            //                     'id_lugar_masaje' => 1,
            //                     'persona'         => ($venta->reserva->next_persona),
            //                     'tiempo_extra'    => false, // 30 min
            //                     'user_id'         => null,
            //                 ]);
            //             }
            //         };
            //         $crearExtras = function($n) use ($reservaId, $venta){
            //             for ($i=0; $i<$n; $i++) {
            //                 Masaje::create([
            //                     'id_reserva'      => $reservaId,
            //                     'horario_masaje'  => null,
            //                     'tipo_masaje'     => null,
            //                     'id_lugar_masaje' => 1,
            //                     'persona'         => ($venta->reserva->next_persona),
            //                     'tiempo_extra'    => true,  // 60 min
            //                     'user_id'         => null,
            //                 ]);
            //             }
            //         };
            //         $subirAExtra = function($n) use ($masajesBaseQuery){
            //             $pend = $masajesBaseQuery->limit($n)->get();
            //             foreach ($pend as $m) {
            //                 $m->tiempo_extra = true;
            //                 $m->save();
            //             }
            //             return $pend->count();
            //         };

            //         // a) Subir a 60 los existentes (no crea nuevos)
            //         if ($tiempoExtraActual) {
            //             $subirAExtra($cantidad);
            //         }
            //         // b) Crear nuevos 60
            //         else if ($tiempoExtraNuevo) {
            //             $crearExtras($cantidad);
            //         }
            //         // c) Nuevo 30 (default)
            //         else {
            //             $crearBase($cantidad);
            //         }

            //     } else {
            //         // ===== OTROS SERVICIOS (Tinaja, Sauna, Almuerzo, etc.) =====
            //         $unitario = (int) ($servicioBD->valor_servicio ?: 0);
            //         $subtotal = $unitario * $cantidad;

            //         DetalleServiciosExtra::create([
            //             'id_consumo'            => $consumo->id,
            //             'id_servicio_extra'     => $servicio_id,
            //             'id_precio_tipo_masaje' => null,
            //             'cantidad_servicio'     => $cantidad,
            //             'subtotal'              => $subtotal,
            //         ]);

            //         $nuevoSubtotal += $subtotal;

            //         // Contadores para Visita SPA
            //         if ($slugServicio === 'sauna')  { $spaSauna  += $cantidad; }
            //         if ($slugServicio === 'tinaja') { $spaTinaja += $cantidad; }

            //         // Almuerzo -> crear menús
            //         if ($slugServicio === 'almuerzo') {
            //             for ($i=1; $i <= $cantidad; $i++) {
            //                 Menu::create([
            //                     'id_reserva'                 => $venta->reserva->id,
            //                     'id_producto_entrada'        => null,
            //                     'id_producto_fondo'          => null,
            //                     'id_producto_acompanamiento' => null,
            //                     'alergias'                   => null,
            //                     'observacion'                => null,
            //                 ]);
            //             }
            //         }
            //     }
            // }

            $nuevoSubtotal     = 0;
            $spaCantidadSauna  = 0;
            $spaCantidadTinaja = 0;

            foreach ($serviciosValidos as $servicio_id => $srv) {

                $servicioBD   = Servicio::findOrFail($servicio_id);
                $slugServicio = $servicioBD->slug ?: str_slug($servicioBD->nombre_servicio);
                $cantidad     = (int) ($srv['cantidad'] ?? 0);

                if ($slugServicio === 'masaje') {
                    // --- MASAJES ---
                    $slugTipo = strtolower($srv['slug_tipo_masaje'] ?? '');
                    $duracion = (int) ($srv['duracion'] ?? 0);

                    $precio = PrecioTipoMasaje::with('tipo')
                        ->whereHas('tipo', function ($q) use ($slugTipo) {$q->where('slug', $slugTipo);})
                        ->where('duracion_minutos', $duracion)
                        ->first();

                    if (! $precio) {
                        throw ValidationException::withMessages(['servicios' => 'Tipo o duración de masaje inválida.']);
                    }

                    $isRelaj = ($precio->tipo->slug === 'relajacion') && in_array($duracion, [30, 60], true) && ! is_null($precio->precio_pareja);
                    $pares   = $isRelaj ? intdiv($cantidad, 2) : 0;
                    $resto   = $cantidad - ($pares * 2);

                    $subtotal = $pares * (int) $precio->precio_pareja + $resto * (int) $precio->precio_unitario;

                    DetalleServiciosExtra::create([
                        'id_consumo'            => $consumo->id,
                        'id_servicio_extra'     => $servicio_id,
                        'id_precio_tipo_masaje' => $precio->id, // <-- se guarda aquí
                        'cantidad_servicio'     => $cantidad,
                        'subtotal'              => $subtotal,
                    ]);

                    $nuevoSubtotal += $subtotal;

                                                                              // Lógica de creación/actualización de registros en `masajes`
                    $tiempoExtraActual = ! empty($srv['tiempo_extra_actual']); // subir base->60
                    $tiempoExtraNuevo  = ! empty($srv['tiempo_extra']);        // crear nuevos 60
                    $reserva           = $venta->reserva;
                    $reservaId         = $reserva->id;

                    $masajesBaseQuery = $reserva->masajes()->where(function ($q) {
                        $q->whereNull('tiempo_extra')->orWhere('tiempo_extra', false);
                    })->orderBy('id');

                    $crearBase = function ($n) use ($reservaId, $venta) {
                        for ($i = 0; $i < $n; $i++) {
                            Masaje::create([
                                'id_reserva'      => $reservaId,
                                'horario_masaje'  => null,
                                'tipo_masaje'     => null,
                                'id_lugar_masaje' => 1,
                                'persona'         => ($venta->reserva->next_persona),
                                'tiempo_extra'    => false,
                                'user_id'         => null,
                            ]);
                        }
                    };
                    $crearExtras = function ($n) use ($reservaId, $venta) {
                        for ($i = 0; $i < $n; $i++) {
                            Masaje::create([
                                'id_reserva'      => $reservaId,
                                'horario_masaje'  => null,
                                'tipo_masaje'     => null,
                                'id_lugar_masaje' => 1,
                                'persona'         => ($venta->reserva->next_persona),
                                'tiempo_extra'    => true,
                                'user_id'         => null,
                            ]);
                        }
                    };
                    $subirAExtra = function ($n) use ($masajesBaseQuery) {
                        $pend = $masajesBaseQuery->limit($n)->get();
                        foreach ($pend as $m) {$m->tiempo_extra = true; $m->save();}
                    };

                    if ($tiempoExtraActual) {
                        $subirAExtra($cantidad);
                    } else if ($tiempoExtraNuevo) {
                        $crearExtras($cantidad);
                    } else {
                        $crearBase($cantidad);
                    }

                } else {
                    // --- OTROS SERVICIOS ---
                    $unitario = (int) ($servicioBD->valor_servicio ?: 0);
                    $subtotal = $unitario * $cantidad;

                    DetalleServiciosExtra::create([
                        'id_consumo'            => $consumo->id,
                        'id_servicio_extra'     => $servicio_id,
                        'id_precio_tipo_masaje' => null, // <-- NULL aquí
                        'cantidad_servicio'     => $cantidad,
                        'subtotal'              => $subtotal,
                    ]);

                    $nuevoSubtotal += $subtotal;

                    if ($slugServicio === 'sauna') {$spaCantidadSauna += $cantidad;}
                    if ($slugServicio === 'tinaja') {$spaCantidadTinaja += $cantidad;}

                    if ($slugServicio === 'almuerzo') {
                        for ($i = 1; $i <= $cantidad; $i++) {
                            Menu::create([
                                'id_reserva'                 => $venta->reserva->id,
                                'id_producto_entrada'        => null,
                                'id_producto_fondo'          => null,
                                'id_producto_acompanamiento' => null,
                                'alergias'                   => null,
                                'observacion'                => null,
                            ]);
                        }
                    }
                }
            }

            // 3) Visitas combinadas (Sauna + Tinaja)
            $spaCombinados = min($spaSauna, $spaTinaja);
            if ($spaCombinados > 0) {
                for ($i = 1; $i <= $spaCombinados; $i++) {
                    Visita::create([
                        'horario_sauna'  => null,
                        'horario_tinaja' => null,
                        'trago_cortesia' => false,
                        'observacion'    => null,
                        'id_reserva'     => $venta->reserva->id,
                        'id_ubicacion'   => $venta->reserva->visitas->first()->id_ubicacion,
                    ]);
                }
            }

            // 4) Totales de consumo
            $consumo->subtotal      = (int) $consumo->subtotal + $nuevoSubtotal;
            $consumo->total_consumo = (int) $consumo->total_consumo + $nuevoSubtotal;
            $consumo->save();
        });

        $venta = Venta::findOrFail($request->id_venta);

        Alert::success('Éxito', 'Servicio extra ingresado correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', $venta->reserva->id);
    }

    public function create($venta)
    {
        $venta   = Venta::findOrFail($venta);
        $tipos   = TipoProducto::all();
        $listado = ['Aguas', 'Bebidas', 'Bebidas Calientes', 'Cervezas', 'Cócteles', 'Jugos Naturales', 'Spritz', 'Mocktails', 'Vinos', 'Sandwich y Pasteleria'];

        $productos = Producto::whereHas('tipoProducto', function ($query) use ($listado) {
            $query->whereIn('nombre', $listado);
        })->get();

        return view('themes.backoffice.pages.consumo.create', [
            'venta'     => $venta,
            'tipos'     => $tipos,
            'productos' => $productos,

        ]);
    }

    public function store(Request $request, Venta $venta)
    {
        // dd($request->all());
        $productosAñadidos = array_filter($request->productos, function ($producto) {
            return isset($producto['cantidad']) && $producto['cantidad'] > 0;
        });

        $productos       = [];
        $cliente         = null;
        $ubicacion       = null;
        $detallesConsumo = [];

        foreach ($productosAñadidos as $id => $producto) {
            $productos[] = $id;
        }

        $nombres = null;
        $nombres = Producto::whereIn('id', $productos)->pluck('nombre')->implode(', ');

        // Iniciar una transacción en la base de datos
        DB::transaction(function () use ($request, &$venta, &$productos, &$cliente, &$ubicacion, &$detallesConsumo, $nombres) {

            // Verificar si ya existe un consumo para esta venta
            $consumo = Consumo::where('id_venta', $request->id_venta)->first();

            // Si no existe, creamos el consumo con valores iniciales
            if (! $consumo) {
                $consumo = Consumo::create([
                    'id_venta'      => $request->id_venta,
                    'subtotal'      => 0,
                    'total_consumo' => 0,
                ]);
            }

            $cliente   = $consumo->venta->reserva->cliente->nombre_cliente;
            $reservaID = $consumo->venta->reserva->id;
            $visita    = Visita::where('id_reserva', $reservaID)->first();
            $ubicacion = Ubicacion::where('id', $visita->id_ubicacion)->first()->nombre;

            // Inicializar variables
            $totalSubtotal = 0;
            $nuevoSubtotal = 0;

            // Siempre se registra con propina activa por defecto.
            // La validación final de si se aplica o no se hace en el cierre de venta.
            $generaPropina = true;

            // Filtrar los productos del request con cantidad válida (mayor que 0)
            $productosValidos = array_filter($request->productos, function ($producto) {
                return isset($producto['cantidad']) && $producto['cantidad'] > 0;
            });

            // Recorrer los productos válidos y crear los detalles de consumo
            foreach ($productosValidos as $producto_id => $producto) {
                $detalle = DetalleConsumo::create([
                    'id_consumo'        => $consumo->id,
                    'id_producto'       => $producto_id,
                    'cantidad_producto' => $producto['cantidad'],
                    'subtotal'          => $producto['valor'] * $producto['cantidad'], // Calcula el subtotal
                    'genera_propina'    => $generaPropina,
                ]);

                $detallesConsumo[] = $detalle;
                // Sumar al subtotal del nuevo consumo
                $nuevoSubtotal += $detalle->subtotal;

                // Verificar si alguno de los productos genera propina

            }

            // Sumar el nuevo subtotal al subtotal actual del consumo
            $consumo->subtotal += $nuevoSubtotal;

            // Calcular la propina solo del nuevo subtotal
            $propina = $consumo->subtotal * 0.1;

            // Recalcular el total del consumo (se añade un 10% en propina)
            $totalConPropina = $consumo->subtotal + $propina;

            // Actualizar el consumo con los nuevos totales
            $consumo->update([
                'subtotal'      => $consumo->subtotal,
                'total_consumo' => $totalConPropina,
            ]);

            $productosEvento = array_map(function ($detalle) use ($request, $cliente, $ubicacion) {
                $producto = Producto::find($detalle->id_producto);
                return [
                    'id'        => $detalle->id,
                    'nombre'    => $producto->nombre,
                    'cantidad'  => $detalle->cantidad_producto,
                    'cliente'   => $cliente ?? 'Cliente Desconocido',      // Ajusta según los datos disponibles
                    'ubicacion' => $ubicacion ?? 'Ubicación Desconocida', // Ajusta según los datos disponibles
                ];
            }, $detallesConsumo);

            event(new NuevoConsumoAgregado([
                'mensaje'   => 'Nuevo consumo agregado ' . $nombres,
                'productos' => $productosEvento,
                'estado'    => 'por-procesar',
            ]));

            // broadcast(new NuevoConsumoAgregado([
            //     'mensaje'=>'Nuevo consumo agregado '.$nombres,
            //     'productos' => $productosEvento,
            //     'estado' => 'por-procesar'
            // ]));

        });

        $venta = Venta::find($request->id_venta);

        // Redirigir con éxito
        Alert::success('Éxito', 'Consumo ingresado correctamente', 'Confirmar')->showConfirmButton();
        return redirect()->route('backoffice.reserva.show', $venta->reserva->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function show(Consumo $consumo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function edit(Consumo $consumo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Consumo $consumo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Consumo  $consumo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Consumo $consumo)
    {
        //
    }

    public function destroyDetalle($tipo, $id)
    {
        $servicioMasaje   = ['masajes', 'masaje', 'Masajes', 'Masaje'];
        $servicioAlmuerzo = ['almuerzos', 'almuerzo', 'Almuerzos', 'Almuerzo'];

        if ($tipo === 'consumo') {
            $detalle = DetalleConsumo::with('consumo')->findOrFail($id);
            $consumo = $detalle->consumo;
            $consumo->subtotal -= $detalle->subtotal;
            $consumo->total_consumo -= $detalle->subtotal * 1.1;

            $consumo->subtotal      = max($consumo->subtotal, 0);
            $consumo->total_consumo = max($consumo->total_consumo, 0);
            $consumo->save();
            $detalle->delete();

        } else if ($tipo === 'servicio') {
            $detalle = DetalleServiciosExtra::with('consumo')->findOrFail($id);
            $consumo = $detalle->consumo;
            $reserva = $consumo->venta->reserva;

            // Verificar si el detalle es de tipo masaje
            if (
                $detalle->servicio &&
                in_array($detalle->servicio->nombre_servicio, $servicioMasaje)
            ) {
                $cantidad = $detalle->cantidad_servicio;

                // Obtener masajes con tiempo_extra = true
                $masajesConExtra = $reserva->masajes()
                    ->where('tiempo_extra', true)
                    ->limit($cantidad)
                    ->get();

                foreach ($masajesConExtra as $masaje) {
                    $masaje->tiempo_extra = false;
                    $masaje->save();
                }
            }

            // $consumo->subtotal -= $detalle->subtotal;
            // $consumo->total_consumo -= $detalle->subtotal;

            // $consumo->subtotal      = max($consumo->subtotal, 0);
            // $consumo->total_consumo = max($consumo->total_consumo, 0);

            // $consumo->save();
            // $detalle->delete();

            // if ($detalle->servicio && in_array(strtolower($detalle->servicio->nombre_servicio), array_map('strtolower',$servicioMasaje))) {
            //     $cantidad = (int) $detalle->cantidad_servicio;
            //     $desde    = $detalle->created_at;

            //     // 1) Revertir upgrades (existían antes y se marcaron después del detalle)
            //     $subidos = $reserva->masajes()
            //         ->where('tiempo_extra', true)
            //         ->where('created_at', '<',  $desde)
            //         ->where('updated_at', '>=', $desde)
            //         ->orderBy('updated_at','desc')
            //         ->limit($cantidad)
            //         ->get();

            //     $revertidos = 0;
            //     foreach ($subidos as $m) {
            //         $m->tiempo_extra = false;
            //         $m->save();
            //         $revertidos++;
            //     }

            //     $faltan = max(0, $cantidad - $revertidos);

            //     // 2) Borrar EXTRAS nuevos creados por el detalle
            //     if ($faltan > 0) {
            //         $extrasNuevos = $reserva->masajes()
            //             ->where('tiempo_extra', true)
            //             ->where('created_at', '>=', $desde)
            //             ->orderBy('id','desc')
            //             ->limit($faltan)
            //             ->get();

            //         foreach ($extrasNuevos as $m) {
            //             $m->delete();
            //         }
            //         $faltan -= $extrasNuevos->count();
            //     }

            //     // 3) Borrar BASES nuevos (caso “actual 30 min”: el 5º de 30')
            //     if ($faltan > 0) {
            //         $basesNuevos = $reserva->masajes()
            //             ->where(function ($q) {
            //                 $q->whereNull('tiempo_extra')->orWhere('tiempo_extra', false);
            //             })
            //             ->where('created_at', '>=', $desde)
            //             ->orderBy('id','desc')
            //             ->limit($faltan)
            //             ->get();

            //         foreach ($basesNuevos as $m) {
            //             $m->delete();
            //         }
            //     }
            // }

            if ($detalle->servicio && in_array(strtolower($detalle->servicio->nombre_servicio), array_map('strtolower', $servicioAlmuerzo))) {
                $cantidad = (int) $detalle->cantidad_servicio;

                for ($i = 1; $i <= $cantidad; $i++) {
                    $reserva->menus->last()->delete();
                }
            }

            // Totales y borrado del detalle
            $consumo->subtotal      = max(0, $consumo->subtotal - $detalle->subtotal);
            $consumo->total_consumo = max(0, $consumo->total_consumo - $detalle->subtotal);
            $consumo->save();
            $detalle->delete();

        } else {
            return back()->with('error', 'Tipo de detalle no válido');
        }

        $tipoCapitalizado = ucfirst($tipo);

        return back()->with('success', $tipoCapitalizado . ' eliminado correctamente');
    }
}
