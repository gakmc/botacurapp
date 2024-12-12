<?php

namespace App\Http\Controllers;

use App\Insumo;
use App\Sector;
use App\UnidadMedida;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class InsumoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request) {
            // Obtener la consulta de búsqueda del input
            $query = trim($request->get('search'));
            // Filtrar los insumos según el sector y la búsqueda
            $cocinaInsumos = Insumo::whereHas('sector', function ($q) {
                $q->where('nombre', 'Cocina');
            })->where('nombre', 'LIKE', '%' . $query . '%')->get();

            $barraInsumos = Insumo::whereHas('sector', function ($q) {
                $q->where('nombre', 'Barra');
            })->where('nombre', 'LIKE', '%' . $query . '%')->get();

            $banoInsumos = Insumo::whereHas('sector', function ($q) {
                $q->where('nombre', 'Baño');
            })->where('nombre', 'LIKE', '%' . $query . '%')->get();

            $masajeInsumos = Insumo::whereHas('sector', function ($q) {
                $q->where('nombre', 'Masaje');
            })->where('nombre', 'LIKE', '%' . $query . '%')->get();

            return view('themes.backoffice.pages.insumo.index', [
                'cocinaInsumos' => $cocinaInsumos,
                'barraInsumos' => $barraInsumos,
                'banoInsumos' => $banoInsumos,
                'masajeInsumos' => $masajeInsumos,
                'sectores' => Sector::all(),
                'unidades' => UnidadMedida::all(),
                'search' => $query,
            ]);

        }

        $sectores = Sector::all();
        $unidades = UnidadMedida::all();

        $cocinaInsumos = Insumo::whereHas('sector', function ($query) {
            $query->where('nombre', 'Cocina');
        })->get();

        $barraInsumos = Insumo::whereHas('sector', function ($query) {
            $query->where('nombre', 'Barra');
        })->get();

        $banoInsumos = Insumo::whereHas('sector', function ($query) {
            $query->where('nombre', 'Baño');
        })->get();

        $masajeInsumos = Insumo::whereHas('sector', function ($query) {
            $query->where('nombre', 'Masaje');
        })->get();

        return view('themes.backoffice.pages.insumo.index', compact('cocinaInsumos', 'barraInsumos', 'banoInsumos', 'masajeInsumos', 'sectores', 'unidades'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sectores = Sector::all();
        $unidades = UnidadMedida::all();

        return view('themes.backoffice.pages.insumo.create', compact('sectores', 'unidades'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request);
        Insumo::create($request->all());
        Alert::success('Éxito', 'Se ha generado el insumo')->showConfirmButton();
        return redirect()->route('backoffice.insumo.index');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function show(Insumo $insumo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function edit(Insumo $insumo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Insumo $insumo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Insumo  $insumo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Insumo $insumo)
    {
        //
    }
}
