<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\Http\Requests\Cliente\StoreRequest;
use App\Http\Requests\Cliente\UpdateRequest;
use App\Reserva;
use App\User;
use App\Visita;
use Illuminate\Http\Request;
// use PDF;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ClienteController extends Controller
{
    public function index(Request $request)
    {

        $this->authorize('index', Cliente::class);
        if ($request) {
            $query = trim($request->get('search'));

            $clientes = Cliente::where('nombre_cliente', 'LIKE', '%' . $query . '%')
                ->orWhere('whatsapp_cliente', 'LIKE', '%' . $query . '%')
                ->orWhere('instagram_cliente', 'LIKE', '%' . $query . '%')
                ->orWhere('correo', 'LIKE', '%' . $query . '%')
                ->orderBy('id', 'asc')->get();

            return view('themes.backoffice.pages.cliente.index', [
                'clientes' => $clientes,
                'search' => $query,
            ]);

        }

        return view('themes.backoffice.pages.cliente.index', [
            'clientes' => Cliente::all(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Cliente::class);
        return view('themes.backoffice.pages.cliente.create');
    }

    public function store(StoreRequest $request, Cliente $cliente)
    {

        $cliente = $cliente->store($request);
        return redirect()->route('backoffice.cliente.show', $cliente);
    }

    public function show(Cliente $cliente)
    {
        $this->authorize('view', $cliente);
        $reservas = $cliente->reservas;
        $masajes = null;

        foreach($reservas as $reserva){
            $masajes = $reserva->masajes;
        }

        return view('themes.backoffice.pages.cliente.show', [
            'cliente' => $cliente,
            'masajes' => $masajes
        ]);
    }

    public function generarPDF(Reserva $reserva)
    {
        $visitas = $reserva->visitas;
        $venta = $reserva->venta;
        $menus = null;
        $masajes = null;
        if (isset($reserva->menus)) {
            $menus = $reserva->menus;
        };

        if (isset($reserva->masajes)) {
            $masajes = $reserva->masajes;
        };

        $saveName = str_replace(' ','_',$reserva->cliente->nombre_cliente);
        $data = [
            'nombre'=>$reserva->cliente->nombre_cliente,
            'fecha_visita'=>$reserva->fecha_visita,
            'personas' => $reserva->cantidad_personas,
            'cantidadMasajes' => $reserva->cantidad_masajes,
            'observacion' => $reserva->observacion,
            
            'programa' => $reserva->programa->nombre_programa,
            'valorPrograma' => $reserva->programa->valor_programa,
            'abono' => $reserva->venta->abono_programa,
            'tipoAbono' => $reserva->venta->tipoTransaccionAbono->nombre,
            'diferencia' => $reserva->venta->diferencia_programa,
            'tipoDiferencia' => (isset($reserva->venta->tipoTransaccionDiferencia->nombre)) ? $reserva->venta->tipoTransaccionDiferencia->nombre : 'No registra',
            'visitas' => $visitas,
            'masajes' => $masajes,
            'menus' => $menus


        ];
        
        $pdf = PDF::loadView('pdf.cliente.viewPDF', $data);
        // return $pdf->download('factura.pdf');
        return $pdf->stream('Visita'.'_'.$saveName.'_'.$reserva->fecha_visita.'.pdf');

    }

    public function edit(Cliente $cliente)
    {
        $this->authorize('update', $cliente);
        return view('themes.backoffice.pages.cliente.edit', [
            'cliente' => $cliente,
        ]);
    }

    public function update(UpdateRequest $request, Cliente $cliente)
    {
        $cliente->my_update($request);
        return redirect()->route('backoffice.cliente.show', $cliente);
    }

    public function destroy(Cliente $cliente)
    {
        //
    }
}
