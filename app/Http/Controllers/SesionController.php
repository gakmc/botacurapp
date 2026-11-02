<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class SesionController extends Controller
{
    /**
     * Estado de la sesión para el control de expiración del backoffice.
     * Si la sesión sigue activa, esta misma petición la renueva.
     * Devuelve el token CSRF vigente para actualizar los formularios abiertos.
     */
    public function estado()
    {
        return response()->json([
            'activa' => Auth::check(),
            'token'  => csrf_token(),
        ]);
    }
}
