<?php

namespace App\Http\Controllers;

use App\Menu;
use App\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class MenuController extends Controller
{

    public function OLDindex()
    {
        Carbon::setLocale('es');

        // Vista actual
        $fechaActual = Carbon::now()->startOfDay();

        $reservas = Reserva::where('fecha_visita', '>=', $fechaActual)
            ->with(['cliente','menus', 'programa.servicios'])
            ->orderBy('fecha_visita')
            ->get();

        // Agrupar reservas por fecha
        $menusPorDia = $reservas->groupBy(function ($reserva) {
            return Carbon::parse($reserva->fecha_visita)->format('d-m-Y');
        });

        // Contar el total de cada plato por día
        $entradasPorDia = $menusPorDia->map(function ($reservasPorFecha) {
            $platosContados = [];

            foreach ($reservasPorFecha as $reserva) {
                // foreach ($reserva->visitas as $visita) {
                    foreach ($reserva->menus as $menu) {
                        if ($menu->id_producto_entrada !== null) {
                            $nombrePlato = $menu->productoEntrada->nombre;
                            if (isset($platosContados[$nombrePlato])) {
                                $platosContados[$nombrePlato]++;
                            } else {
                                $platosContados[$nombrePlato] = 1;
                            }
                        }
                        
                    }
                // }
            }

            return $platosContados;
        });


        $fondosPorDia = $menusPorDia->map(function ($reservasPorFecha) {
            $platosContados = [];

            foreach ($reservasPorFecha as $reserva) {
                // foreach ($reserva->visitas as $visita) {
                    foreach ($reserva->menus as $menu) {
                        if ($menu->id_producto_fondo !== null) {
                            if (isset($platosContados[$menu->productoFondo->nombre])) {
                                $platosContados[$menu->productoFondo->nombre]++;
                            } else {
                                $platosContados[$menu->productoFondo->nombre] = 1;
                            }
                        }
                    }
                // }
            }

            return $platosContados;
        });

        $acompanamientosPorDia = $menusPorDia->map(function ($reservasPorFecha) {
            $platosContados = [];

            foreach ($reservasPorFecha as $reserva) {
                // foreach ($reserva->visitas as $visita) {
                    foreach ($reserva->menus as $menu) {
                        if ($menu->productoAcompanamiento !== null) {
                            if (isset($platosContados[$menu->productoAcompanamiento->nombre])) {
                                $platosContados[$menu->productoAcompanamiento->nombre]++;
                            } else {
                                $platosContados[$menu->productoAcompanamiento->nombre] = 1;
                            }
                        }
                    }
                // }
            }

            return $platosContados;
        });

        // Paginación manual de los días
        $perPage = 1; // Número de días por página
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $menusPorDia->slice(($currentPage - 1) * $perPage, $perPage)->all();

        // Crear el paginador manualmente
        $menusPaginados = new LengthAwarePaginator($currentItems, $menusPorDia->count(), $perPage, $currentPage);
        $menusPaginados->setPath(request()->url());

        return view('themes.backoffice.pages.cocina.index', compact('menusPaginados', 'entradasPorDia', 'fondosPorDia', 'acompanamientosPorDia'));
    }


    public function index(Request $request)
    {
        Carbon::setLocale('es');

        // Fecha inicial (d-m-Y)
        $fecha = $request->get('fecha');
        if (!$fecha) {
            $fecha = now()->format('d-m-Y');
        }

        return view('themes.backoffice.pages.cocina.index', [
            'fechaInicial' => $fecha,
        ]);
    }

    public function dia(Request $request)
    {
        Carbon::setLocale('es');

        $fechaDMY = $request->get('fecha'); // dd-mm-YYYY (desde JS)
        if (!$fechaDMY) {
            return response()->json(['message' => 'Falta fecha'], 422);
        }

        try {
            $fechaISO = Carbon::createFromFormat('d-m-Y', $fechaDMY)->toDateString(); // YYYY-mm-dd
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Formato de fecha inválido, se espera d-m-Y'], 422);
        }

        // DEBUG (puedes dejarlo un rato)
        // Log::info('Cocina dia()', ['fechaDMY' => $fechaDMY, 'fechaISO' => $fechaISO]);

        $reservas = Reserva::query()
            ->where(function ($q) use ($fechaDMY, $fechaISO) {
                // "YYYY-MM-DD"
                $q->orWhere('fecha_visita', $fechaISO);
            })
            ->with([
                'cliente:id,nombre_cliente,whatsapp_cliente,correo',
                'programa:id,nombre_programa',
                'menus:id,id_reserva,id_producto_entrada,id_producto_fondo,id_producto_acompanamiento,alergias,observacion',
                'menus.productoEntrada:id,nombre',
                'menus.productoFondo:id,nombre',
                'menus.productoAcompanamiento:id,nombre',
            ])
            ->orderBy('id', 'asc')
            ->get();


        // Log::info('Cocina reservas encontradas', ['count' => $reservas->count()]);

        // Contadores por día
        $entradas = [];
        $fondos = [];
        $acomps = [];

        foreach ($reservas as $reserva) {
            foreach ($reserva->menus as $menu) {
                if ($menu->id_producto_entrada && $menu->productoEntrada) {
                    $n = $menu->productoEntrada->nombre;
                    $entradas[$n] = ($entradas[$n] ?? 0) + 1;
                }
                if ($menu->id_producto_fondo && $menu->productoFondo) {
                    $n = $menu->productoFondo->nombre;
                    $fondos[$n] = ($fondos[$n] ?? 0) + 1;
                }
                if ($menu->productoAcompanamiento) {
                    $n = $menu->productoAcompanamiento->nombre;
                    $acomps[$n] = ($acomps[$n] ?? 0) + 1;
                }
            }
        }

        $reservasPayload = $reservas->map(function ($r) {
            return [
                'id' => $r->id,
                'cliente' => $r->cliente ? $r->cliente->nombre_cliente : 'Sin cliente',
                'cantidad_personas' => $r->cantidad_personas ?? 0,
                'programa' => $r->programa ? $r->programa->nombre_programa : 'Sin programa',
                'observacion_reserva' => $r->observacion ?? 'Sin Observaciones',
                'avisado_en_cocina' => $r->avisado_en_cocina,
                'avisar_url' => route('backoffice.reserva.avisar', $r->id), // (sin backoffice, por tu prefix)
                'menus' => $r->menus->map(function ($m) {
                    return [
                        'entrada' => ($m->id_producto_entrada && $m->productoEntrada) ? $m->productoEntrada->nombre : null,
                        'fondo' => ($m->id_producto_fondo && $m->productoFondo) ? $m->productoFondo->nombre : null,
                        'acompanamiento' => $m->productoAcompanamiento ? $m->productoAcompanamiento->nombre : null,
                        'alergias' => $m->alergias,
                        'observacion' => $m->observacion,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'fecha' => $fechaDMY,
            'hoy' => now()->format('d-m-Y') === $fechaDMY,
            'entradas' => $entradas,
            'fondos' => $fondos,
            'acompanamientos' => $acomps,
            'reservas' => $reservasPayload,
        ]);
    }
    


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Menu $menu)
    {
        //
    }

    public function edit(Menu $menu)
    {
        //
    }

    public function update(Request $request, Menu $menu)
    {
        //
    }

    public function destroy(Menu $menu)
    {
        //
    }
}
