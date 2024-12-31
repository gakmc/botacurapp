<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\Http\Requests\Cliente\StoreRequest;
use App\Http\Requests\Cliente\UpdateRequest;
use App\Reserva;
use App\User;
use Illuminate\Http\Request;
use PDF;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('index', User::class);
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
        $this->authorize('create', User::class);
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
        return view('themes.backoffice.pages.cliente.show', [
            'cliente' => $cliente,
        ]);
    }

    public function generarPDF(Reserva $reserva)
    {
        $saveName = str_replace(' ','_',$reserva->cliente->nombre_cliente);
        $data = [
            'nombre'=>$reserva->cliente->nombre_cliente,
            'fecha_visita'=>$reserva->fecha_visita,
            'programa' => $reserva->programa->nombre_programa,
            'personas' => $reserva->cantidad_personas,
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
