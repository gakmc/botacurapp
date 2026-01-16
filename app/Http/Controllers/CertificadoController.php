<?php
namespace App\Http\Controllers;

use App\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CertificadoController extends Controller
{

    public function index()
    {
        //
    }

    public function create(User $user)
    {
        return view("themes.backoffice.pages.user.certificados.antiguedad", compact("user"));
    }

    public function store(User $user, Request $request)
    {

        $request->validate([
            'rut' => 'required|string|max:20',
        ]);

        $fechaEmision = Carbon::now()->startOfDay();
        $inicio       = Carbon::parse($user->created_at)->startOfDay();

        $diff            = $inicio->diff($fechaEmision);
        $antiguedadTexto = trim(
            ($diff->y ? $diff->y . ' año(s) ' : '') .
            ($diff->m ? $diff->m . ' mes(es) ' : '') .
            ($diff->d ? $diff->d . ' día(s)' : '')
        );
        if ($antiguedadTexto === '') {
            $antiguedadTexto = '0 día(s)';
        }

        $data = [
            'usuario'         => $user,
            'nombre'         => $request->name,
            'rut'             => $request->rut,
            'fechaIngreso'    => $inicio,
            'fechaEmision'    => $fechaEmision,
            'antiguedadTexto' => $antiguedadTexto,
            'emitido_por'     => auth()->user(), // opcional
        ];

        $pdf = PDF::loadView('pdf.certificado.antiguedad.viewPDF', $data)->setPaper('letter');

        return $pdf->stream('certificado_antiguedad_' . $request->name . '.pdf');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
