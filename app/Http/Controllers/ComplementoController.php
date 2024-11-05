<?php

namespace App\Http\Controllers;

use App\CategoriaCompra;
use App\Sector;
use App\TipoDocumento;
use App\TipoProducto;
use App\TipoTransaccion;
use App\Ubicacion;
use App\UnidadMedida;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ComplementoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('themes.backoffice.pages.complemento.index', [
            'categorias' => CategoriaCompra::all(),
            'sectores' => Sector::all(),
            'documentos' => TipoDocumento::all(),
            'transacciones' => TipoTransaccion::all(),
            'ubicaciones' => Ubicacion::all(),
            'unidades' => UnidadMedida::all(),
            'tipoProductos' => TipoProducto::with('sector')->get(),
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

        switch ($request->input('table')) {
            case 'sectores':
                Sector::create(['nombre' => $request->input('nombre')]);
                break;

            case 'ubicaciones':
                Ubicacion::create(['nombre' => $request->input('nombre')]);
                break;
            case 'unidades_medidas':
                UnidadMedida::create([
                    'nombre' => $request->input('nombre'),
                    'abreviatura' => $request->input('abreviatura'),
                    'tipo' => $request->input('tipo'),
                    'descripcion' => $request->input('descripcion'),
                ]);
                break;
            case 'tipos_documentos':
                TipoDocumento::create([
                    'nombre' => $request->input('nombre'),
                    'descripcion' => $request->input('descripcion'),
                ]);
                break;

            case 'tipos_transacciones':
                TipoTransaccion::create(['nombre' => $request->input('nombre')]);
                break;

            case 'categoria_compras':
                CategoriaCompra::create(['nombre' => $request->input('nombre')]);
                break;

            case 'tipos_productos':
                TipoProducto::create([
                    'nombre' => $request->input('nombre'),
                    'id_sector' => $request->input('sector')
                ]);
                break;
        }

        $elemento = $request->input('nombre');
        $registro = $request->input('table');

        $registro = str_replace('_', ' ', $registro);

        $registro = ucwords($registro);

        Alert::success('Éxito', "Elemento $elemento añadido a $registro")->showConfirmButton();
        return redirect()->route('backoffice.complemento.index');
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
        $table = $request->input('table');

        switch ($table) {
            case 'sectores':
                $actualizar = Sector::findOrFail($id);
                $actualizar->update($request->all());
                break;

            case 'ubicaciones':
                $actualizar = Ubicacion::findOrFail($id);
                $actualizar->update($request->all());
                break;
            case 'unidades_medidas':
                $actualizar = UnidadMedida::findOrFail($id);
                $actualizar->update($request->all());
                break;
            case 'tipos_documentos':
                $actualizar = TipoDocumento::findOrFail($id);
                $actualizar->update($request->all());
                break;

            case 'tipos_transacciones':
                $actualizar = TipoTransaccion::findOrFail($id);
                $actualizar->update($request->all());
                break;

            case 'categoria_compras':
                $actualizar = CategoriaCompra::findOrFail($id);
                $actualizar->update($request->all());
                break;

            case 'tipos_productos':
                $actualizar = TipoProducto::findOrFail($id);
                $actualizar->update($request->all());
                break;
        }

        $elemento = $request->input('nombre');
        $registro = $request->input('table');

        $registro = str_replace('_', ' ', $registro);

        $registro = ucwords($registro);

        Alert::success('Éxito', "Elemento $elemento actualizado en la tabla $registro")->showConfirmButton();
        return redirect()->route('backoffice.complemento.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $table = $request->input('table');

        switch ($table) {
            case 'sectores':
                $eliminar = Sector::findOrFail($id);
                $eliminar->delete();
                break;

            case 'ubicaciones':
                $eliminar = Ubicacion::findOrFail($id);
                $eliminar->delete();
                break;
            case 'unidades_medidas':
                $eliminar = UnidadMedida::findOrFail($id);
                $eliminar->delete();
                break;

            case 'tipos_documentos':
                $eliminar = TipoDocumento::findOrFail($id);
                $eliminar->delete();
                break;

            case 'tipos_productos':
                $eliminar = TipoProducto::findOrFail($id);
                $eliminar->delete();
                break;

            case 'tipos_transacciones':
                $eliminar = TipoTransaccion::findOrFail($id);
                $eliminar->delete();
                break;

            case 'categoria_compras':
                $eliminar = CategoriaCompra::findOrFail($id);
                $eliminar->delete();
                break;
        }

        // $elemento = $request->input('nombre');
        // $registro = $request->input('table');

        // $registro = str_replace('_', ' ', $registro);

        // $registro = ucwords($registro);

        Alert::success('Éxito', "Elemento eliminado correctamente")->showConfirmButton();
        return redirect()->route('backoffice.complemento.index');



    }
}
