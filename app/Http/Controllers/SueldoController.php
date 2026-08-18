<?php
namespace App\Http\Controllers;

use App\Consumo;
use App\HonorarioBte;
use App\Propina;
use App\Services\CsvPagoBancoService;
use App\Sueldo;
use App\SueldoPagado;
use App\User;
use App\VentaDirecta;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class SueldoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function indexOld(Request $request)
    // {
    //     $mes = $request->input('mes', now()->month);
    //     $anio = $request->input('anio', now()->year);

    //     // Obtener usuarios que tengan al menos un sueldo ese mes/año
    //     $usuarios = User::whereHas('sueldos', function ($query) use ($mes, $anio) {
    //         $query->whereMonth('dia_trabajado', $mes)
    //               ->whereYear('dia_trabajado', $anio);
    //     })
    //     ->with(['sueldos' => function ($query) use ($mes, $anio) {
    //         $query->whereMonth('dia_trabajado', $mes)
    //               ->whereYear('dia_trabajado', $anio)
    //               ->orderBy('dia_trabajado', 'asc');
    //     }])
    //     ->get();

    //     // Obtener todos los meses/años únicos en que el usuario trabajó
    //     $fechasDisponibles = Sueldo::selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
    //     ->groupBy('mes', 'anio')
    //     ->orderBy('anio', 'desc')
    //     ->orderBy('mes', 'desc')
    //     ->get();

    //     return view('themes.backoffice.pages.sueldo.index', [
    //         'usuarios' => $usuarios,
    //         'mes' => $mes,
    //         'anio' => $anio,
    //         'fechasDisponibles' => $fechasDisponibles
    //     ]);
    // }

    public function OLDindex(Request $request)
    {
        $mes  = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $sueldos = Sueldo::with('user')
            ->whereMonth('dia_trabajado', $mes)
            ->whereYear('dia_trabajado', $anio)
            ->orderBy('dia_trabajado')
            ->get();

        $semanas = [];

        foreach ($sueldos as $sueldo) {
            $fecha        = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            $rango = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');

            $roles = $sueldo->user->list_roles();

            $userId   = $sueldo->user->id;
            $userName = $sueldo->user->name;
            $userRole = $sueldo->user->roles;

            if (! isset($semanas[$rango])) {
                $semanas[$rango] = [];
            }

            if (! isset($semanas[$rango][$userId])) {
                $semanas[$rango][$userId] = [
                    'role'     => $roles,
                    'name'     => $userName,
                    'dias'     => 0,
                    'sueldos'  => 0,
                    'propinas' => 0,
                    'total'    => 0,
                    'user_id'  => $userId,
                    'inicio'   => $inicioSemana->format('Y-m-d'),
                    'fin'      => $finSemana->format('Y-m-d'),
                ];
            }

            $semanas[$rango][$userId]['dias'] += 1;
            $semanas[$rango][$userId]['sueldos'] += $sueldo->valor_dia;
            if (in_array($roles, ['Masoterapeuta'])) {
                $semanas[$rango][$userId]['propinas'] = 0;
            } else {
                $semanas[$rango][$userId]['propinas'] += $sueldo->sub_sueldo - $sueldo->valor_dia;
            }
            $semanas[$rango][$userId]['total'] += $sueldo->total_pagar;
        }

        // ordenar las semanas cronológicamente
        uksort($semanas, function ($a, $b) use ($anio) {
            $dateA = Carbon::createFromFormat('d M Y', substr($a, 0, 6) . ' ' . $anio);
            $dateB = Carbon::createFromFormat('d M Y', substr($b, 0, 6) . ' ' . $anio);
            return $dateA->timestamp <=> $dateB->timestamp;
        });

        $fechasDisponibles = Sueldo::selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        $pagosRealizados = SueldoPagado::select('*')->get();

        return view('themes.backoffice.pages.sueldo.index', [
            'semanas'           => $semanas,
            'mes'               => $mes,
            'anio'              => $anio,
            'fechasDisponibles' => $fechasDisponibles,
            'pagosRealizados'   => $pagosRealizados,
        ]);
    }

    /**
     * Arma la estructura $semanas (usuario x semana, con BTE ya matcheado
     * y bono/motivo/confirmado ya pisados desde sueldos_pagados) para un
     * mes/año dado. La usan tanto index() como exportarCsv(), para que el
     * CSV pueda armarse sin depender de qué esté seleccionado en pantalla.
     */
    private function construirSemanas($mes, $anio)
    {
        $sueldos = Sueldo::with('user')
            ->whereMonth('dia_trabajado', $mes)
            ->whereYear('dia_trabajado', $anio)
            ->orderBy('dia_trabajado')
            ->get();

        // BTE (boletas de honorarios) de los usuarios que boletean, agrupadas por rut,
        // para poder mostrar bruto/retención/neto en la semana donde se emitió cada una.
        $honorariosPorRut = HonorarioBte::anio($anio)
            ->whereIn('rut_emisor', User::where('boletea', true)->whereNotNull('rut')->pluck('rut'))
            ->get()
            ->groupBy('rut_emisor');

        $semanas = [];

        foreach ($sueldos as $sueldo) {
            // Saltar si el usuario fue eliminado o no existe
            if (! $sueldo->user) {
                continue;
            }

            // Usar el valor raw del atributo (sin accessor) para parsear la fecha sin ambigüedad
            $rawDate      = $sueldo->getAttributes()['dia_trabajado'];
            $fecha        = Carbon::parse($rawDate);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            $rango  = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
            $roles  = $sueldo->user->list_roles();
            $esMaso = is_array($roles) ? in_array('Masoterapeuta', $roles)
                : (stripos((string) $roles, 'Masoterapeuta') !== false);

            $userId   = $sueldo->user->id;
            $userName = $sueldo->user->name;

            if (! isset($semanas[$rango])) {
                $semanas[$rango] = [];
            }

            if (! isset($semanas[$rango][$userId])) {
                // BTE de este usuario emitida dentro de esta semana (si boletea)
                $boletea = (bool) $sueldo->user->boletea;
                $bteRow  = null;

                if ($boletea && $sueldo->user->rut) {
                    $bteSemana = $honorariosPorRut->get($sueldo->user->rut, collect());
                    $bteRow    = $bteSemana->first(function ($h) use ($inicioSemana, $finSemana) {
                        return $h->fecha_emision
                            && $h->fecha_emision->between($inicioSemana->copy()->startOfDay(), $finSemana->copy()->endOfDay());
                    });
                }

                $semanas[$rango][$userId] = [
                    'role'          => $roles,
                    'name'          => $userName,
                    'dias'          => 0, // aquí guardaremos "días" o "masajes" según rol
                    'sueldos'       => 0,
                    'propinas'      => 0,
                    'bono'          => 0,
                    'motivo'        => '',
                    'confirmado'    => false,
                    'total'         => 0,
                    'user_id'       => $userId,
                    'inicio'        => $inicioSemana->format('Y-m-d'),
                    'fin'           => $finSemana->format('Y-m-d'),
                    'boletea'       => $boletea,
                    'bte_bruto'     => $bteRow->monto_bruto ?? 0,
                    'bte_retencion' => $bteRow->monto_retenido ?? 0,
                    'bte_neto'      => $bteRow ? ($bteRow->monto_pagado ?: ($bteRow->monto_bruto - $bteRow->monto_retenido)) : 0,
                    'bte_estado'    => ($bteRow && $bteRow->estado !== 'Anulada') ? 'emitida' : null,
                    'bte_folio'     => $bteRow->folio ?? null,
                ];

                // Si es masoterapeuta, calculamos una sola vez los MASAJES de la semana
                if ($esMaso) {
                    $ini = $inicioSemana->toDateString();
                    $fin = $finSemana->toDateString();

                    $cantMasajes = DB::table('masajes as m')
                        ->join('reservas as r', 'r.id', '=', 'm.id_reserva')
                        ->whereBetween('r.fecha_visita', [$ini, $fin])
                        ->where('m.user_id', $userId) // masoterapeuta
                        ->count();                    // cada fila = 1 masaje (incluye pareja como 2)

                    $semanas[$rango][$userId]['dias'] = (int) $cantMasajes; // “días” = masajes
                }
            }

            if (! $esMaso) {
                $semanas[$rango][$userId]['dias'] += 1;
            }

            $semanas[$rango][$userId]['sueldos'] += $esMaso ? $sueldo->total_pagar : $sueldo->valor_dia;
            $semanas[$rango][$userId]['propinas'] += $esMaso ? 0 : ($sueldo->sub_sueldo - $sueldo->valor_dia);
            $semanas[$rango][$userId]['total'] += $sueldo->total_pagar;
        }

        // Orden cronológico de semanas
        uksort($semanas, function ($a, $b) use ($anio) {
            // "21 Jul - 27 Jul" → "21 Jul 2026"
            $dateA = Carbon::createFromFormat('d M Y', substr($a, 0, 6) . ' ' . $anio);
            $dateB = Carbon::createFromFormat('d M Y', substr($b, 0, 6) . ' ' . $anio);
            return $dateA->timestamp <=> $dateB->timestamp;
        });

        // Bono/motivo/confirmado ya guardados (por guardarBonos() y/o
        // confirmarPago(), independientes entre sí).
        $pagosRealizados = SueldoPagado::all();

        foreach ($pagosRealizados as $pago) {
            $inicioSemana = Carbon::parse($pago->semana_inicio);
            $finSemana    = Carbon::parse($pago->semana_fin);

            $rango  = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
            $userId = $pago->user_id;

            if (isset($semanas[$rango]) && isset($semanas[$rango][$userId])) {
                $semanas[$rango][$userId]['bono']       = (int) $pago->bono;
                $semanas[$rango][$userId]['motivo']     = $pago->motivo;
                $semanas[$rango][$userId]['confirmado'] = (bool) $pago->confirmado;

                // Si el bono debe sumarse al total de la semana:
                $semanas[$rango][$userId]['total'] += (int) $pago->bono;
            }
        }

        // Recalcular 'total' = neto + propinas + bono. Hasta acá 'total' se
        // arrastraba desde total_pagar (que no sabe nada de BTE), así que a
        // quienes boletean nunca se les estaba descontando la retención del
        // total a pagar. Acá se corrige usando el neto de BTE cuando aplica.
        foreach ($semanas as $rango => &$usuariosSemana) {
            foreach ($usuariosSemana as $userId => &$datos) {
                $netoBase = ($datos['boletea'] && $datos['bte_bruto'] > 0)
                    ? $datos['bte_neto']
                    : $datos['sueldos'];

                $datos['total'] = $netoBase + $datos['propinas'] + $datos['bono'];
            }
        }
        unset($usuariosSemana, $datos);

        return [$semanas, $pagosRealizados];
    }

    public function index(Request $request)
    {
        $mes  = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        [$semanas, $pagosRealizados] = $this->construirSemanas($mes, $anio);

        $fechasDisponibles = Sueldo::selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return view('themes.backoffice.pages.sueldo.index', compact(
            'semanas', 'mes', 'anio', 'fechasDisponibles', 'pagosRealizados'
        ));
    }

    /**
     * Guarda un bono/motivo "general" (mismo monto y motivo) para el grupo
     * de usuarios seleccionado con los checkboxes de la columna "Sel. bono"
     * (independiente de los checkboxes de "Pagar"). No toca 'confirmado'.
     */
    public function guardarBonos(Request $request)
    {
        if (!auth()->user()->has_role(config('app.admin_role'))) {
            abort(403);
        }

        $request->validate([
            'semana_inicio' => 'required|date',
            'semana_fin'    => 'required|date',
            'bono'          => 'nullable|string',
            'motivo'        => 'nullable|string',
            'usuarios_bono' => 'required|array|min:1',
        ]);

        $bonoValor = (int) str_replace(['$', '.', ','], '', $request->input('bono', 0));
        $motivo    = $request->input('motivo');

        foreach ($request->input('usuarios_bono', []) as $userId) {
            SueldoPagado::updateOrCreate(
                [
                    'user_id'       => $userId,
                    'semana_inicio' => $request->semana_inicio,
                    'semana_fin'    => $request->semana_fin,
                ],
                [
                    'bono'       => $bonoValor,
                    'motivo'     => $motivo,
                    'fecha_pago' => now()->format('Y-m-d'),
                ]
            );
        }

        $cantidad = count($request->input('usuarios_bono', []));

        return back()->with('success', "Bono guardado para {$cantidad} usuario(s).");
    }

    /**
     * Exporta el CSV de transferencia a terceros (BancoEstado Empresas)
     * para TODOS los sueldos pendientes (no confirmados) del mes/año
     * indicado — no depende de ninguna selección con checkbox.
     */
    public function exportarCsv(Request $request, CsvPagoBancoService $csvService)
    {
        if (!auth()->user()->has_role(config('app.admin_role'))) {
            abort(403);
        }

        $mes  = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);

        [$semanas, ] = $this->construirSemanas($mes, $anio);

        $seleccionados = [];
        foreach ($semanas as $usuariosSemana) {
            foreach ($usuariosSemana as $datos) {
                if (!empty($datos['confirmado'])) {
                    continue; // ya se confirmó que se le transfirió, no se repite
                }
                if ((float) $datos['total'] <= 0) {
                    continue;
                }
                $seleccionados[] = [
                    'user_id' => $datos['user_id'],
                    'total'   => $datos['total'],
                    'inicio'  => $datos['inicio'],
                    'fin'     => $datos['fin'],
                ];
            }
        }

        if (empty($seleccionados)) {
            return back()->with('error', 'No hay sueldos pendientes de pago para exportar en este período.');
        }

        $resultado = $csvService->generar($seleccionados);

        if (!empty($resultado['omitidos'])) {
            $nombres = array_map(function ($o) {
                $nombre = $o['user'] ? $o['user']->name : 'usuario desconocido';
                return "{$nombre} ({$o['motivo']})";
            }, $resultado['omitidos']);

            session()->flash('warning', 'Se omitieron del CSV: ' . implode('; ', $nombres));
        }

        if (empty(trim($resultado['csv'])) || substr_count($resultado['csv'], "\n") === 0) {
            return back()->with('error', 'No se pudo generar el CSV: nadie con datos bancarios completos y pendiente de pago.');
        }

        $nombreArchivo = 'pago_sueldos_' . $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '_' . now()->format('His') . '.csv';

        return response($resultado['csv'], 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ]);
    }

    public function adminViewSueldos(User $user, $anio, $mes, Request $request)
    {
        $userId = $user->id;

        // Obtener todos los meses/años únicos en que el usuario trabajó
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // Si no se selecciona fecha, usar la más reciente disponible
        $fechaSeleccionada = $fechasDisponibles->first();
        $currentMonth      = ($fechaSeleccionada ? $mes : now()->month);
        $currentYear       = ($fechaSeleccionada ? $anio : now()->year);
        // $currentMonth = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        // $currentYear = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

        // Obtener todos los sueldos del mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $mes)
            ->whereYear('dia_trabajado', $anio)
            ->orderBy('dia_trabajado', 'asc')
            ->get();

        $masajesPorDia = [];
        foreach ($sueldos as $sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado)->toDateString();

            $masajesPorDia[$fecha] = DB::table('masajes as m')
                ->join('reservas as r', 'r.id', '=', 'm.id_reserva')
                ->where('m.user_id', $userId)
                ->whereDate('r.fecha_visita', $fecha)
                ->count();
        }

        // // Agrupar los sueldos por semana
        $sueldosAgrupados = $sueldos->groupBy(function ($sueldo) {
            $fecha        = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            return $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
        });

        // Bono/motivo guardados para este usuario, pisados una sola vez por
        // semana (no por día, para no repetir/multiplicar el valor).
        $pagosUsuario = SueldoPagado::where('user_id', $userId)->get();

        $sueldosAgrupados->each(function ($sueldosSemana) use ($pagosUsuario) {
            $fechaTrabajo = Carbon::parse($sueldosSemana->first()->dia_trabajado)->toDateString();

            $pagoSemana = $pagosUsuario->first(function ($p) use ($fechaTrabajo) {
                $ini = Carbon::parse($p->semana_inicio)->toDateString();
                $fin = Carbon::parse($p->semana_fin)->toDateString();

                return ($fechaTrabajo >= $ini && $fechaTrabajo <= $fin);
            });

            $bono   = $pagoSemana ? (int) $pagoSemana->bono : 0;
            $motivo = $pagoSemana ? $pagoSemana->motivo : null;

            foreach ($sueldosSemana as $sueldo) {
                $sueldo->setAttribute('bono', $bono);
                $sueldo->setAttribute('motivo', $motivo);
            }
        });

        if ($user->has_role('masoterapeuta')) {

            return view('themes.backoffice.pages.sueldo.admin_view_maso', [
                'sueldosAgrupados'  => $sueldosAgrupados,
                'mes'               => $mes,
                'anio'              => $anio,
                'fechasDisponibles' => $fechasDisponibles,
                'user'              => $user,
                'masajesPorDia'     => $masajesPorDia,
            ]);

        } else {

            return view('themes.backoffice.pages.sueldo.admin_view', [
                'sueldosAgrupados'  => $sueldosAgrupados,
                'mes'               => $mes,
                'anio'              => $anio,
                'fechasDisponibles' => $fechasDisponibles,
                'user'              => $user,
            ]);

        }

    }

    public function OLDview(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener mes y año del request o usar el mes y año actuales como predeterminado
        $currentMonth = $request->input('mes', now()->month);
        $currentYear  = $request->input('anio', now()->year);

        // Filtrar registros por el mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15); // Paginación con 10 registros por página

        // Verificar la autorización para al menos un sueldo
        // if ($sueldos->isNotEmpty()) {
        $this->authorize('view', $sueldos->first());
        // } else {
        //     abort(403);
        // }

        return view('themes.backoffice.pages.sueldo.view', [
            'sueldos' => $sueldos,
            'mes'     => $currentMonth,
            'anio'    => $currentYear,
            'user'    => $user,
        ]);
    }

public function view(User $user, Request $request)
{
    $userId = $user->id;

    $fechasDisponibles = Sueldo::where('id_user', $userId)
        ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
        ->groupBy('mes', 'anio')
        ->orderBy('anio', 'desc')
        ->orderBy('mes', 'desc')
        ->get();

    $fechaSeleccionada = $fechasDisponibles->first();
    $currentMonth = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
    $currentYear  = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

    $sueldos = Sueldo::where('id_user', $userId)
        ->whereMonth('dia_trabajado', $currentMonth)
        ->whereYear('dia_trabajado', $currentYear)
        ->orderBy('dia_trabajado', 'asc')
        ->get();

    if ($sueldos->isNotEmpty()) {
        $this->authorize('view', $sueldos->first());
    }

    // Traemos TODOS los sueldos_pagados del usuario (o filtra por mes si quieres)
    $pagos = SueldoPagado::where('user_id', $userId)->get();

    $sueldosAgrupados = $sueldos->groupBy(function ($sueldo) {
        $fecha = Carbon::parse($sueldo->dia_trabajado);
        $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

        return $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
    });

    // Para cada grupo semanal, buscamos el pago cuyo rango contenga el dia_trabajado
    $sueldosAgrupados->each(function ($items) use ($pagos) {

        $fechaTrabajo = Carbon::parse($items->first()->dia_trabajado)->toDateString();

        $pagoSemana = $pagos->first(function ($p) use ($fechaTrabajo) {
            $ini = Carbon::parse($p->semana_inicio)->toDateString();
            $fin = Carbon::parse($p->semana_fin)->toDateString();

            return ($fechaTrabajo >= $ini && $fechaTrabajo <= $fin);
        });

        
        $bono   = $pagoSemana ? (int) $pagoSemana->bono : 0;
        $motivo = $pagoSemana ? $pagoSemana->motivo : null;

        foreach ($items as $sueldo) {
            $sueldo->setAttribute('bono', $bono);
            $sueldo->setAttribute('motivo', $motivo);
        }
    });



    return view('themes.backoffice.pages.sueldo.view', [
        'sueldosAgrupados' => $sueldosAgrupados,
        'mes' => $currentMonth,
        'anio' => $currentYear,
        'fechasDisponibles' => $fechasDisponibles,
        'user' => $user,
    ]);
}

    public function viewOLDFUNCIONANDO(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener todos los meses/años únicos en que el usuario trabajó
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // Si no se selecciona fecha, usar la más reciente disponible
        $fechaSeleccionada = $fechasDisponibles->first();
        $currentMonth      = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        $currentYear       = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

        // Obtener todos los sueldos del mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->get();

        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        }

        // Agrupar los sueldos por semana
        $sueldosAgrupados = $sueldos->groupBy(function ($sueldo) {
            $fecha        = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            return $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
        });

        return view('themes.backoffice.pages.sueldo.view', [
            'sueldosAgrupados'  => $sueldosAgrupados,
            'mes'               => $currentMonth,
            'anio'              => $currentYear,
            'fechasDisponibles' => $fechasDisponibles,
            'user'              => $user,
        ]);
    }

    public function viewSueldos(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener todos los meses/años únicos en que el usuario trabajó
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // Si no se selecciona fecha, usar la más reciente disponible
        $fechaSeleccionada = $fechasDisponibles->first();

        $currentMonth = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        $currentYear  = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15);

        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        }

        return view('themes.backoffice.pages.sueldo.view', [
            'sueldos'           => $sueldos,
            'mes'               => $currentMonth,
            'anio'              => $currentYear,
            'fechasDisponibles' => $fechasDisponibles,
            'user'              => $user,
        ]);
    }

    public function detalle_diario(User $user, $anio, $mes, $dia)
    {
        $user  = $user->load('sueldos', 'propinas');
        $fecha = Carbon::createFromDate($anio, $mes, $dia);

        $sueldo = Sueldo::where('id_user', $user->id)
            ->whereDate('dia_trabajado', $fecha)
            ->with('propina')
            ->first();

        // Obtener propinas del día con las relaciones necesarias
        $propinas = Propina::with('propinable')
            ->whereDate('fecha', $fecha)
            ->get();

        // Filtrar solo las asignaciones del usuario actual
        $asignaciones          = collect();
        $total_propina_usuario = 0;

        // foreach ($propinas as $propina) {
        //     $pivot = $propina->users()->where('users.id', $user->id)->first();
        //     if ($pivot) {
        //         $asignaciones->push((object)[
        //             'nombre_cliente' => optional($propina->propinable->venta->reserva->cliente)->nombre_cliente ?? 'Desconocido',
        //             'monto_asignado' => $pivot->pivot->monto_asignado,
        //         ]);
        //         $total_propina_usuario += $pivot->pivot->monto_asignado;
        //     }
        // }

        foreach ($propinas as $propina) {
            $pivot = $propina->users()->where('users.id', $user->id)->first();
            if ($pivot) {
                $nombre_cliente = 'Desconocido';

                if ($propina->propinable) {
                    if ($propina->propinable_type == VentaDirecta::class) {
                        $nombre_cliente = 'Venta Directa';
                    } elseif ($propina->propinable_type == Consumo::class) {
                        $nombre_cliente = optional($propina->propinable->venta->reserva->cliente)->nombre_cliente ?? 'Desconocido';
                    }
                }

                $asignaciones->push((object) [
                    'nombre_cliente' => $nombre_cliente,
                    'monto_asignado' => $pivot->pivot->monto_asignado,
                ]);

                $total_propina_usuario += $pivot->pivot->monto_asignado;
            }
        }

        return view('themes.backoffice.pages.sueldo.detalle_diario', [
            'user'                  => $user,
            'fecha'                 => $fecha,
            'sueldo'                => $sueldo,
            'asignaciones'          => $asignaciones,
            'total_propina_usuario' => $total_propina_usuario,
        ]);
    }

    // public function view_maso(User $user, Request $request)
    // {
    //     $userId = $user->id;

    //     // Obtener mes y año del request o usar el mes y año actuales como predeterminado
    //     $currentMonth = $request->input('mes', now()->month);
    //     $currentYear = $request->input('anio', now()->year);

    //     // Filtrar registros por el mes seleccionado
    //     $sueldos = Sueldo::where('id_user', $userId)
    //         ->whereMonth('dia_trabajado', $currentMonth)
    //         ->whereYear('dia_trabajado', $currentYear)
    //         ->orderBy('dia_trabajado', 'asc')
    //         ->paginate(15); // Paginación con 10 registros por página

    //     // Verificar la autorización para al menos un sueldo
    //     if ($sueldos->isNotEmpty()) {
    //         $this->authorize('view', $sueldos->first());
    //     } else {
    //         abort(403);
    //     }

    //     return view('themes.backoffice.pages.sueldo.view_maso', [
    //         'sueldos' => $sueldos,
    //         'mes' => $currentMonth,
    //         'anio' => $currentYear,
    //         'user' => $user,
    //     ]);
    // }

    public function view_maso(User $user, Request $request)
    {
        $userId = $user->id;

        $currentMonth = $request->input('mes', now()->month);
        $currentYear  = $request->input('anio', now()->year);

        // Query base de sueldos del usuario
        $baseQuery = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear);

        // Paginación por día
        $sueldos = (clone $baseQuery)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15);

        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        } else {
            abort(403);
        }

        // 🔹 Cantidad de masajes por cada día de sueldo (usando MASAJES.created_at)
        $masajesPorDia = [];
        foreach ($sueldos as $sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado)->toDateString();

            $masajesPorDia[$fecha] = DB::table('masajes as m')
                ->join('reservas as r', 'r.id', '=', 'm.id_reserva')
                ->where('m.user_id', $userId)
                ->whereDate('r.fecha_visita', $fecha)
                ->count();
        }

        // Total masajes del mes en plata
        $totalMasajesMes = (clone $baseQuery)->sum('sub_sueldo');

        // Bonos del mes desde sueldos_pagados
        $bonos = DB::table('sueldos_pagados')
            ->where('user_id', $userId)
            ->whereMonth('fecha_pago', $currentMonth)
            ->whereYear('fecha_pago', $currentYear)
            ->orderBy('fecha_pago')
            ->get();

        $totalBonosMes  = $bonos->sum('bono');
        $totalMesGlobal = $totalMasajesMes + $totalBonosMes;

        return view('themes.backoffice.pages.sueldo.view_maso', [
            'sueldos'         => $sueldos,
            'mes'             => $currentMonth,
            'anio'            => $currentYear,
            'user'            => $user,
            'masajesPorDia'   => $masajesPorDia, // ['2025-05-13' => 2, ...]
            'bonos'           => $bonos,
            'totalMasajesMes' => $totalMasajesMes,
            'totalBonosMes'   => $totalBonosMes,
            'totalMesGlobal'  => $totalMesGlobal,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {
            $sueldos = $request->input('sueldos');

            foreach ($sueldos as $sueldo) {
                // Actualiza si existe o crea un nuevo registro
                Sueldo::updateOrCreate(
                    [
                        'dia_trabajado' => $sueldo['dia_trabajado'],
                        'id_user'       => $sueldo['id_user'],
                    ],
                    [
                        'valor_dia'   => $sueldo['valor_dia'],
                        'sub_sueldo'  => $sueldo['sub_sueldo'],
                        'total_pagar' => $sueldo['total_pagar'],
                    ]
                );
            }

            Alert::toast('Se almacenaron los sueldos correctamente', 'success')->toToast('center');
            return redirect()->back();

        } catch (Exception $e) {

            Alert::toast('No se almacenaron los sueldos ' . $e->getMessage(), 'error')->toToast('center');
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }

    }

    // public function actualizarSueldoBase(Request $request)
    // {
    //     $request->validate([
    //         'sueldoBase' => 'required|numeric',
    //     ]);

    //     // Recuperar el sueldo base actual del cache
    //     $sueldoActual = Cache::get('sueldoBase');

    //     // Verificar si el valor es diferente al actual
    //     if ($sueldoActual !== $request->sueldoBase) {
    //         // Guardar el nuevo valor en cache
    //         Cache::forever('sueldoBase', $request->sueldoBase);

    //         // Redirigir con un mensaje de éxito
    //         return redirect()->back()->with('success', 'El sueldo base se ha actualizado correctamente.');

    //     }else{
    //         // Redirigir con un mensaje indicando que no hubo cambios
    //         return redirect()->back()->with('info', 'El sueldo base es el mismo, no se realizaron cambios.');
    //     }

    // }

    public function store_maso(Request $request)
    {

        try {
            $sueldos = $request->input('sueldos');

            foreach ($sueldos as $sueldo) {
                // Actualiza si existe o crea un nuevo registro
                Sueldo::updateOrCreate(
                    [
                        'dia_trabajado' => $sueldo['dia_trabajado'],
                        'id_user'       => $sueldo['id_user'],
                    ],
                    [
                        'valor_dia'   => $sueldo['valor_dia'],
                        'sub_sueldo'  => $sueldo['sub_sueldo'],
                        'total_pagar' => $sueldo['total_pagar'],
                    ]
                );
            }

            Alert::toast('Se almacenaron los sueldos correctamente', 'success')->toToast('top');
            return redirect()->back();

        } catch (Exception $e) {

            Alert::toast('No se almacenaron los sueldos ' . $e->getMessage(), 'error')->toToast('top');
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

}
