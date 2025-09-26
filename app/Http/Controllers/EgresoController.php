<?php

namespace App\Http\Controllers;

use App\CategoriaCompra;
use App\Egreso;
use App\PagoEgreso;
use App\Proveedor;
use App\TipoDocumento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EgresoController extends Controller
{

    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $hoy = Carbon::now();
        $mesActual = (int) $hoy->month;
        $anioActual = (int) $hoy->year;

        // Meses que tienen pagos (agregados por mes/año)
        $egresos = DB::table('pagos_egresos as p')
            ->join('egresos as e', 'e.id', '=', 'p.egreso_id')
            ->selectRaw('
                MONTH(p.fecha_pago) as mes,
                YEAR(p.fecha_pago)  as anio,
                SUM(p.monto)        as total_mes,
                COUNT(p.id)         as cantidad,
                SUM(CASE WHEN e.tipo_documento_id = 2 THEN 1 ELSE 0 END) as cantidad_facturas,
                SUM(CASE WHEN e.tipo_documento_id = 1 THEN 1 ELSE 0 END) as cantidad_boletas
            ')
            ->whereYear('p.fecha_pago', $anio)
            ->groupBy('mes','anio')
            ->orderBy('mes')
            ->get();

        // Inyectar el mes actual (para permitir pagar) si el año seleccionado es el actual
        if ($anio === $anioActual && !$egresos->contains('mes', $mesActual)) {
            $egresos->push((object) [
                'mes'               => $mesActual,
                'anio'              => $anioActual,
                'total_mes'         => 0,
                'cantidad'          => 0,
                'cantidad_facturas' => 0,
                'cantidad_boletas'  => 0,
            ]);
            // ordenar nuevamente por mes
            $egresos = $egresos->sortBy('mes')->values();
        }

        // Años disponibles (según pagos realizados) + asegurar el año actual
        $añosDisponibles = DB::table('pagos_egresos')
            ->selectRaw('YEAR(fecha_pago) as anio')
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        if (!$añosDisponibles->contains($anioActual)) {
            $añosDisponibles->prepend($anioActual);
        }

        return view('themes.backoffice.pages.egreso.index', compact('egresos', 'anio', 'añosDisponibles'));
    }

    public function OLDindex(Request $request)
    {
        $anio = $request->input('anio', now()->year);

        $egresos = Egreso::selectRaw('MONTH(fecha) as mes, YEAR(fecha) as anio, SUM(total) as total_mes, COUNT(*) as cantidad, SUM(CASE WHEN tipo_documento_id = 2 THEN 1 ELSE 0 END) as cantidad_facturas, SUM(CASE WHEN tipo_documento_id = 1 THEN 1 ELSE 0 END) as cantidad_boletas')
            ->whereYear('fecha', $anio)
            ->groupBy('mes', 'anio')
            ->orderBy('mes')
            ->get();

        $añosDisponibles = Egreso::selectRaw('YEAR(fecha) as anio')
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return view('themes.backoffice.pages.egreso.index', compact('egresos', 'anio', 'añosDisponibles'));

    }

    // public function index(Request $request)
    // {
    //     $mes = $request->input('mes', now()->month);
    //     $anio = $request->input('anio', now()->year);

    //     $egresos = Egreso::with(['categoria', 'subcategoria', 'proveedor'])
    //         ->whereMonth('fecha', $mes)
    //         ->whereYear('fecha', $anio)
    //         ->latest('fecha')
    //         ->get();

    //     $fechasDisponibles = Egreso::selectRaw('MONTH(fecha) as mes, YEAR(fecha) as anio')
    //         ->groupBy('mes', 'anio')
    //         ->orderBy('anio', 'desc')
    //         ->orderBy('mes', 'desc')
    //         ->get();

    //     return view('themes.backoffice.pages.egreso.index', [
    //         'egresos' => $egresos,
    //         'mes' => $mes,
    //         'anio' => $anio,
    //         'fechasDisponibles' => $fechasDisponibles,
    //     ]);
    // }
    
    public function index_mes($anio, $mes)
    {
        $egresos = Egreso::with(['categoria', 'subcategoria', 'proveedor', 'tipo_documento','pagos'])
                    ->get();

        $fijos = Egreso::with(['categoria', 'subcategoria', 'proveedor', 'tipo_documento','pagos'])
                    ->where('categoria_id',1)
                    ->get();

        $variables = Egreso::with(['categoria', 'subcategoria', 'proveedor', 'tipo_documento','pagos'])
                    ->where('categoria_id',2)
                    ->get();

        // dd($egresos);
        return view('themes.backoffice.pages.egreso.index_mes', compact('egresos', 'mes', 'anio', 'fijos', 'variables'));
    }


    // public function OLDindex_mes($anio, $mes)
    // {
    //     $egresos = Egreso::with(['categoria', 'subcategoria', 'proveedor', 'tipo_documento'])
    //                 ->whereMonth('fecha',$mes)
    //                 ->whereYear('fecha',$anio)
    //                 ->orderBy('fecha')
    //                 ->get();

    //     // $semanas = [];

    //     // foreach ($egresos as $egreso) {
    //     //     $fecha = Carbon::parse($egreso->fecha);
    //     //     $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
    //     //     $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

    //     //     $rango = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');

    //     //     $tipoDocumentoId = $egreso->tipo_documento->id;
    //     //     $categoriaId = $egreso->categoria->id;
    //     //     $categoriaName = $egreso->categoria->nombre;
    //     //     $subcategoriaName = $egreso->subcategoria->nombre;

    //     //     if (!isset($semanas[$rango])) {
    //     //         $semanas[$rango] = [];
    //     //     }

    //     //     if (!isset($semanas[$rango][$categoriaId])) {
    //     //         $semanas[$rango][$categoriaId] = [
    //     //             'categoria' => $categoriaName,
    //     //             'subcategoria' => $subcategoriaName,
    //     //             'neto' => 0,
    //     //             'iva' => 0,
    //     //             'total' => 0,
    //     //             'categoria_id' => $categoriaId,
    //     //             'inicio' => $inicioSemana->format('Y-m-d'),
    //     //             'fin' => $finSemana->format('Y-m-d')
    //     //         ];
    //     //     }

    //     //     $semanas[$rango][$categoriaId]['neto'] += $egreso->neto;
    //     //     $semanas[$rango][$categoriaId]['iva'] += $egreso->iva;
    //     //     $semanas[$rango][$categoriaId]['total'] += $egreso->total;
    //     // }

    //     // uksort($semanas, function ($a, $b) use ($anio) {
    //     //     $dateA = Carbon::createFromFormat('d M Y', substr($a, 0, 6) . $anio);
    //     //     $dateB = Carbon::createFromFormat('d M Y', substr($b, 0, 6) . $anio);
    //     //     return $dateA->timestamp <=> $dateB->timestamp;
    //     // });

    //     $semanas = [];

    //     foreach ($egresos as $egreso) {
    //         $fecha = \Carbon\Carbon::parse($egreso->fecha);
    //         $inicioSemana = $fecha->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    //         $finSemana = $fecha->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

    //         $clave = $inicioSemana->format('Y-m-d'); // clave segura
    //         $rango = $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');

    //         if (!isset($semanas[$clave])) {
    //             $semanas[$clave] = [
    //                 'rango' => $rango,
    //                 'Gastos Fijos' => [],
    //                 'Gastos Variables' => [],
    //             ];
    //         }

    //         $tipo = $egreso->categoria_id == 1 ? 'Gastos Fijos' : 'Gastos Variables';
    //         $semanas[$clave][$tipo][] = $egreso;

    //         if (!isset($semanas[$clave]['totales'])) {
    //             $semanas[$clave]['totales'] = [
    //                 'neto' => 0,
    //                 'iva' => 0,
    //                 'impuesto_incluido' => 0,
    //                 'total' => 0,
    //             ];
    //         }

    //         $semanas[$clave]['totales']['neto'] += $egreso->neto;
    //         $semanas[$clave]['totales']['iva'] += $egreso->iva;
    //         $semanas[$clave]['totales']['impuesto_incluido'] += $egreso->impuesto_incluido;
    //         $semanas[$clave]['totales']['total'] += $egreso->total;
    //     }

    //     // ordenar por clave de fecha (descendente)
    //     uksort($semanas, function($a, $b) {
    //         return strtotime($a) - strtotime($b); // orden ascendente por fecha
    //     });



    //     $totalMes = [
    //         'neto' => 0,
    //         'iva' => 0,
    //         'impuesto_incluido' => 0,
    //         'total' => 0,
    //     ];

    //     foreach ($semanas as $semana) {
    //         $totalMes['neto'] += $semana['totales']['neto'];
    //         $totalMes['iva'] += $semana['totales']['iva'];
    //         $totalMes['impuesto_incluido'] += $semana['totales']['impuesto_incluido'];
    //         $totalMes['total'] += $semana['totales']['total'];
    //     }

    //     return view('themes.backoffice.pages.egreso.index_mes', compact('semanas','mes','anio', 'egresos', 'totalMes'));
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categorias = CategoriaCompra::all();
        $proveedores = Proveedor::all();
        $tipoDocumentos = TipoDocumento::all();
        return view('themes.backoffice.pages.egreso.create', compact('categorias', 'proveedores','tipoDocumentos'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $request->merge([
            'neto'    => (int) str_replace(['$', '.', ','], '', $request->neto),
            'iva'    => (int) str_replace(['$', '.', ','], '', $request->iva),
            'impuesto_incluido' => (int) str_replace(['$', '.', ','], '', $request->impuesto_incluido ?? 0),
            'total'    => (int) str_replace(['$', '.', ','], '', $request->total),
        ]);
        
        // dd($request->fecha);
        $request->validate([
            'tipo_documento_id' => 'required|exists:tipos_documentos,id',
            'categoria_id' => 'required|exists:categorias_compras,id',
            'subcategoria_id' => 'required|exists:subcategorias_compras,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'fecha'        => 'required|date',
            'total'        => 'required|numeric|min:1',
            'folio'        => 'nullable|string|max:8',
            'neto'        => 'nullable|numeric',
            'iva'        => 'nullable|numeric',
            'impuesto_incluido'        => 'nullable|numeric',
        ],[
            'tipo_documento_id.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento_id.exists' => 'El tipo de documento seleccionado no es válido.',

            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',

            'subcategoria_id.required' => 'La subcategoría es obligatoria.',
            'subcategoria_id.exists' => 'La subcategoría seleccionada no es válida.',

            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',

            'fecha.required' => 'La fecha de emisión es obligatoria.',
            'fecha.date' => 'La fecha ingresada no es válida.',

            'total.required' => 'El total es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total debe ser mayor a cero.',

            'folio.string' => 'El folio debe ser un texto.',
            'folio.max' => 'El folio no puede tener más de 8 caracteres.',

            'neto.numeric' => 'El monto neto debe ser un número.',
            'iva.numeric' => 'El IVA debe ser un número.',
            'impuesto_incluido.numeric' => 'El impuesto incluido debe ser un número.',
        ]);

        $egreso = Egreso::create($request->all());

        $anio = \Carbon\Carbon::parse($egreso->fecha)->year;
        $mes = \Carbon\Carbon::parse($egreso->fecha)->month;

        return redirect()->route('backoffice.egreso.mes', [$anio, $mes])->with('success', 'Egreso creado correctamente.');        

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
        $egreso = Egreso::findOrFail($id);

        $categorias = CategoriaCompra::all();
        $proveedores = Proveedor::all();
        $tipoDocumentos = TipoDocumento::all();

        $anio = Carbon::parse($egreso->fecha)->year;
        $mes = Carbon::parse($egreso->fecha)->month;
        $dia = Carbon::parse($egreso->fecha)->day;
        
        return view('themes.backoffice.pages.egreso.edit', compact('egreso', 'categorias', 'proveedores','tipoDocumentos', 'anio', 'mes', 'dia'));

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
        
        $egreso = Egreso::findOrFail($id);
        
        $request->merge([
            'neto'    => (int) str_replace(['$', '.', ','], '', $request->neto),
            'iva'    => (int) str_replace(['$', '.', ','], '', $request->iva),
            'impuesto_incluido'    => (int) str_replace(['$', '.', ','], '', $request->impuesto_incluido ?? 0),
            'total'    => (int) str_replace(['$', '.', ','], '', $request->total),
        ]);
        
        // dd($request->all());

        $request->validate([
            'tipo_documento_id' => 'required|exists:tipos_documentos,id',
            'categoria_id' => 'required|exists:categorias_compras,id',
            'subcategoria_id' => 'required|exists:subcategorias_compras,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'fecha'        => 'required|date',
            'total'        => 'required|numeric|min:1',
            'folio'        => 'nullable|string|max:8',
            'neto'        => 'nullable|numeric',
            'iva'        => 'nullable|numeric',
            'impuesto_incluido'        => 'nullable|numeric',
        ],[
            'tipo_documento_id.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento_id.exists' => 'El tipo de documento seleccionado no es válido.',

            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',

            'subcategoria_id.required' => 'La subcategoría es obligatoria.',
            'subcategoria_id.exists' => 'La subcategoría seleccionada no es válida.',

            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',

            'fecha.required' => 'La fecha de emisión es obligatoria.',
            'fecha.date' => 'La fecha ingresada no es válida.',

            'total.required' => 'El total es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total debe ser mayor a cero.',

            'folio.string' => 'El folio debe ser un texto.',
            'folio.max' => 'El folio no puede tener más de 8 caracteres.',

            'neto.numeric' => 'El monto neto debe ser un número.',
            'iva.numeric' => 'El IVA debe ser un número.',
            'impuesto_incluido.numeric' => 'El impuesto incluido debe ser un número.',
        ]);

        $egreso->update($request->all());

        $anio = \Carbon\Carbon::parse($egreso->fecha)->year;
        $mes = \Carbon\Carbon::parse($egreso->fecha)->month;

        return redirect()->route('backoffice.egreso.mes', [$anio, $mes])->with('success', 'Egreso actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $egreso = Egreso::findOrFail($id);

        $egreso->delete();

        return redirect()->route('backoffice.egreso.index')->with('success', 'Egreso eliminado correctamente.');
    }

    public function pago_fijo(Request $request)
    {

        foreach ($request->monto_pagado as $idx => $valor) {
            $egreso = Egreso::findOrFail($idx);
            $valor = (int) str_replace(['$','.',','],'',$valor);

            // dd($egreso, $valor);

            PagoEgreso::create([
                'egreso_id' => $egreso->id,
                'folio' => null,
                'monto' => $valor,
                'neto' => null,
                'iva' => null,
                'impuesto_incluido' => null,
                'fecha_pago' => Carbon::now(),
            ]);

        }


        return back()->with('success', 'Pagos fijos registrados correctamente.');
    }


    public function pago_variable(Request $request)
    {
        
        foreach ($request->items as $idegreso => $egreso) {
            $neto = (int) str_replace(['$','.',','],'',$egreso['neto']);
            $iva = (int) str_replace(['$','.',','],'',$egreso['iva']);
            $impuesto = 0;
            if (isset($egreso['impuesto_incluido'])) {
                
                $impuesto = (int) str_replace(['$','.',','],'',$egreso['impuesto_incluido']);

            }
            $monto = (int) str_replace(['$','.',','],'',$egreso['monto']);

            dd([
                'egreso_id' => $idegreso,
                'folio' => $egreso['folio'],
                'monto' => $monto,
                'neto' => $neto,
                'iva' => $iva,
                'impuesto_incluido' => $impuesto,
                'fecha_pago' => Carbon::now(),
            ]);

            dd($neto, $iva, $impuesto, $monto);

            PagoEgreso::create([
                'egreso_id' => $idegreso,
                'folio' => $egreso['folio'],
                'monto' => $monto,
                'neto' => $neto,
                'iva' => $iva,
                'impuesto_incluido' => $impuesto,
                'fecha_pago' => Carbon::now(),
            ]);

        }
        dd($request->items);
        

        return back()->with('success', 'Pagos fijos registrados correctamente.');
    }
}
