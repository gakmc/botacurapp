<?php

namespace App\Http\Controllers;

use App\Insumo;
use App\Producto;
use App\TipoProducto;
use App\UnidadMedida;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productos = Producto::activos()->with('tipoProducto')->get();
        return view('themes.backoffice.pages.producto.index', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipos = TipoProducto::all();
        $insumos = Insumo::all();
        $unidades = UnidadMedida::all();
        return view('themes.backoffice.pages.producto.create', compact('tipos', 'insumos', 'unidades'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'nombre' => 'required|string|max:150',
            'valor' => 'required|integer|max:99999',
            'id_tipo_producto' => 'required|integer|exists:tipos_productos,id',
            'insumos' => 'required|array|min:1',
            'insumos.*.id_insumo' => 'required|integer|exists:insumos,id',
            'insumos.*.cantidad_insumo_usar' => 'required|numeric|min:1',
            'insumos.*.id_unidad_medida' => 'required|integer|exists:unidades_medidas,id',
        ], [
            'nombre.required' => 'El campo Nombre es requerido',
            'nombre.max' => 'El nombre excede la cantidad de caracteres permitidos',
            'valor.required' => 'El campo Valor es requerido',
            'valor.max' => 'El valor excede la cantidad de caracteres permitidos',
            'id_tipo_producto.required' => 'El campo Tipo Producto es requerido',
            'id_tipo_producto.integer' => 'El campo Tipo Producto debe ser de tipo Numerico',
            'id_tipo_producto.exists' => 'Tipo Producto seleccionado, no existe',
            'insumos.required' => 'Debe ingresar al menos un insumo',
            'insumos.min' => 'Debe ingresar al menos un insumo',
            'insumos.*.id_insumo.required' => 'El insumo es requerido',
            'insumos.*.id_insumo.exists' => 'El insumo seleccionado no existe',
            'insumos.*.cantidad_insumo_usar.required' => 'La cantidad de insumo a usar es requerida',
            'insumos.*.cantidad_insumo_usar.min' => 'La cantidad de insumo a usar minimo debe ser 1',
            'insumos.*.id_unidad_medida.required' => 'La unidad de medida es requerida',
        ]);

        $producto = Producto::create([
            'nombre' => $validatedData['nombre'],
            'valor' => $validatedData['valor'],
            'id_tipo_producto' => $validatedData['id_tipo_producto'],
        ]);

        $valorProducto = 0;
        $utilidad = 0;

        foreach ($validatedData['insumos'] as $insumoData) {
            // Obtener los insumos con sus datos filtrando por los seleccionados
            $insumo = Insumo::find($insumoData['id_insumo']);
            // Calcular la utilidad del insumo en la cantidad de productos
            $utilidad_producto = $this->calcularUtilidad($insumoData['cantidad_insumo_usar'], $insumo->id_unidad_medida);

            // // Realizar los cálculos de acuerdo a la unidad de medida
            // switch ($insumo->id_unidad_medida) {
            //     case 1: // ID de litros en la unidad de medida
            //         $utilidad = 1000 / $insumoData['cantidad_insumo_usar']; // Litros a mililitros
            //         break;
            //     case 2: // ID de kilos en la unidad de medida
            //         $utilidad = 1000 / $insumoData['cantidad_insumo_usar']; // Kilos a gramos
            //         break;
            //     default:
            //         // Para otras unidades de medida, simplemente retorna la cantidad de insumo
            //         $utilidad = $insumoData['cantidad_insumo_usar'];
            //         break;
            // }

            // Calcular el costo total del insumo en el producto
            $total_costo_producto = $insumo->valor / $utilidad_producto;

            // Redondear el número hacia arriba
            $total_costo_producto = ceil($total_costo_producto);

            // Sumar el costo del insumo al valor total del producto
            $valorProducto += $total_costo_producto;



            $producto->insumos()->attach(
                 $insumoData['id_insumo'], [
                'cantidad_insumo_usar' => $insumoData['cantidad_insumo_usar'],
                'id_unidad_medida' => $insumoData['id_unidad_medida'],
                'total_costo_producto' => intval($total_costo_producto),
                'utilidad_producto' => $utilidad_producto,
            ]);


        }

        Alert::success('Éxito', 'Se ha generado el producto con sus insumos')->showConfirmButton();
        return redirect()->route('backoffice.producto.show', $producto);
    }

    private function calcularUtilidad($cantidad_usar, $unidad_medida_id)
    {

        switch ($unidad_medida_id) {
            case 1: // ID de litros en la unidad de medida
                $cantidad_usar = 1000 / $cantidad_usar;
                return $cantidad_usar; // Convertir a mililitros
            case 2: // ID de kilos en la unidad de medida
                $cantidad_usar = 1000 / $cantidad_usar;
                return $cantidad_usar; // Convertir a gramos

            default:
                return $cantidad_usar; // Si no requiere conversión, devolver la cantidad original
        }
    }

    public function show(Producto $producto)
    {
        $producto = Producto::with(['tipoProducto','insumos.unidadMedidaPivot'])->findOrFail($producto->id);

        $totalCostoProducto = $producto->insumos->sum(function($insumo){
            return $insumo->pivot->total_costo_producto;          
        });

        return view('themes.backoffice.pages.producto.show', compact('producto', 'totalCostoProducto'));
    }

    public function edit(Producto $producto)
    {
        //
    }

    public function update(Request $request, Producto $producto)
    {
        //
    }

    public function destroy(Producto $producto)
    {
        //
    }

    public function index_inactivos()
    {
        $productos = Producto::inactivos()->with('tipoProducto')->get();
        return view('themes.backoffice.pages.producto.index_inactivos', compact('productos'));
    }

    public function cambiarEstado(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'estado' => 'nullable|in:activo,inactivo'
        ]);

        $producto->update(['estado' => $data['estado']]);

        return response()->json([
            'ok'     => true,
            'estado' => $producto->estado,
            'msg'    => $producto->estado === 'activo' ? 'Producto activado' : 'Producto desactivado',
        ]);

    }
}
