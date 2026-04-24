<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UsuarioRetencionController extends Controller
{
    public function update(Request $request, User $user)
    {
        // dd($request->all());
        $user->impuestoUsuario()->updateOrCreate(
            [],
            [
                'retiene_impuestos' => $request->boolean('retiene_impuestos'),
                'retencion_desde' => now(),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'retiene' => $user->fresh()->retiene_impuestos]);
        }

        return back()->with('success', 'Retención actualizada correctamente.');
    }
}
