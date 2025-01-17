<?php

namespace App\Http\Controllers;

use App\Sueldo;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RealRashid\SweetAlert\Facades\Alert;

class SueldoController extends Controller
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

    public function view(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener mes y año del request o usar el mes y año actuales como predeterminado
        $currentMonth = $request->input('mes', now()->month);
        $currentYear = $request->input('anio', now()->year);

        // Filtrar registros por el mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15); // Paginación con 10 registros por página

        // Verificar la autorización para al menos un sueldo
        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        } else {
            abort(403);
        }

        return view('themes.backoffice.pages.sueldo.view', [
            'sueldos' => $sueldos,
            'mes' => $currentMonth,
            'anio' => $currentYear,
            'user' => $user,
        ]);
    }

    public function view_maso(User $user, Request $request)
    {
        $userId = $user->id;

        // Obtener mes y año del request o usar el mes y año actuales como predeterminado
        $currentMonth = $request->input('mes', now()->month);
        $currentYear = $request->input('anio', now()->year);

        // Filtrar registros por el mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->paginate(15); // Paginación con 10 registros por página

        // Verificar la autorización para al menos un sueldo
        if ($sueldos->isNotEmpty()) {
            $this->authorize('view', $sueldos->first());
        } else {
            abort(403);
        }

        return view('themes.backoffice.pages.sueldo.view_maso', [
            'sueldos' => $sueldos,
            'mes' => $currentMonth,
            'anio' => $currentYear,
            'user' => $user,
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
                        'id_user' => $sueldo['id_user'],
                    ],
                    [
                        'valor_dia' => $sueldo['valor_dia'],
                        'sub_sueldo' => $sueldo['sub_sueldo'],
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

    public function actualizarSueldoBase(Request $request)
    {
        $request->validate([
            'sueldoBase' => 'required|numeric',
        ]);

        // Recuperar el sueldo base actual del cache
        $sueldoActual = Cache::get('sueldoBase');


        // Verificar si el valor es diferente al actual
        if ($sueldoActual !== $request->sueldoBase) {
            // Guardar el nuevo valor en cache
            Cache::forever('sueldoBase', $request->sueldoBase);
    
            // Redirigir con un mensaje de éxito
            return redirect()->back()->with('success', 'El sueldo base se ha actualizado correctamente.');

        }else{
            // Redirigir con un mensaje indicando que no hubo cambios
            return redirect()->back()->with('info', 'El sueldo base es el mismo, no se realizaron cambios.');
        }

    }

    public function store_maso(Request $request)
    {

        // dd($request);

        try {
            $sueldos = $request->input('sueldos');

            foreach ($sueldos as $sueldo) {
                // Actualiza si existe o crea un nuevo registro
                Sueldo::updateOrCreate(
                    [
                        'dia_trabajado' => $sueldo['dia_trabajado'],
                        'id_user' => $sueldo['id_user'],
                    ],
                    [
                        'valor_dia' => $sueldo['valor_dia'],
                        'sub_sueldo' => $sueldo['sub_sueldo'],
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
