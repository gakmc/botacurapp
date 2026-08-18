<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Requests\User\DatosBancariosRequest;

/**
 * CRUD (listar + editar) de los datos bancarios / boletea de cada
 * trabajador: rut, boletea, banco, tipo_cuenta_bancaria,
 * numero_cuenta_bancaria, correo_personal.
 *
 * No incluye create/delete: estos campos viven sobre un usuario ya
 * existente (el alta de usuarios se hace desde el CRUD de Usuarios).
 */
class DatosBancariosController extends Controller
{
    public function index()
    {
        $this->authorize('index', User::class);

        $usuarios = auth()->user()->visible_users()
            ->sortBy('name')
            ->values();

        return view('themes.backoffice.pages.user.datos_bancarios.index', [
            'usuarios' => $usuarios,
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('themes.backoffice.pages.user.datos_bancarios.edit', [
            'user' => $user,
        ]);
    }

    public function update(DatosBancariosRequest $request, User $user)
    {
        $user->update([
            'rut' => $request->rut,
            'boletea' => $request->boolean('boletea'),
            'banco' => $request->banco,
            'tipo_cuenta_bancaria' => $request->tipo_cuenta_bancaria,
            'numero_cuenta_bancaria' => $request->numero_cuenta_bancaria,
            'correo_personal' => $request->correo_personal,
        ]);

        alert('Éxito', 'Datos bancarios actualizados', 'success')->showConfirmButton();

        return redirect()->route('backoffice.datos-bancarios.index');
    }
}
