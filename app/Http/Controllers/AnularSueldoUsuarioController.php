<?php

namespace App\Http\Controllers;

use App\AnularSueldoUsuario;
use App\User;
use Illuminate\Http\Request;

class AnularSueldoUsuarioController extends Controller
{
    public function index()
    {
        // Obtenemos todos los usuarios con sus roles y sobrescrituras de sueldo
        $usuarios = \App\User::with(['roles.rangoSueldo', 'anularSueldo'])->get();

        return view('themes.backoffice.pages.sueldo.por-usuario.index', compact('usuarios'));
    }

    public function create(Request $request)
    {
        // $usuarios = User::doesntHave('anularSueldo')->get(); // Solo los que aún no tienen sobrescritura

        $usuario = User::findOrFail($request->user_id);

        return view('themes.backoffice.pages.sueldo.por-usuario.create', compact('usuario'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'salario' => (int) str_replace(['$','.',','],'',$request->salario),
        ]);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'salario' => 'required|numeric',
            'motivo' => 'nullable|string|max:255',
        ]);

        $funcionario = User::findOrFail($request->user_id);
        // dd($request->salario);
        AnularSueldoUsuario::create([
            'user_id' => $request->user_id,
            'salario' => $request->salario,
            'motivo' => $request->motivo,
            'creado_por' => auth()->user()->id,
        ]);

        return redirect()->route('backoffice.usuario-sueldo.index')->with('success','Sueldo de '.$funcionario->name.' actualizado');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $anularSueldoUsuario = AnularSueldoUsuario::findOrFail($id);
        $funcionario = User::findOrFail($anularSueldoUsuario->user_id);

        return view('themes.backoffice.pages.sueldo.por-usuario.edit', compact('anularSueldoUsuario','funcionario'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'salario' => (int) str_replace(['$','.',','],'',$request->salario),
        ]);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'salario' => 'required|numeric',
            'motivo' => 'nullable|string|max:255',
        ]);

        $sueldo = AnularSueldoUsuario::findOrFail($id);
        $funcionario = User::findOrFail($request->user_id);

        $sueldo->update([
            'user_id' => $request->user_id,
            'salario' => $request->salario,
            'motivo' => $request->motivo,
        ]);

        return redirect()->route('backoffice.usuario-sueldo.index')->with('success','Sueldo de '.$funcionario->name.' actualizado');
    }

    public function destroy($id)
    {
        $sueldo = AnularSueldoUsuario::findOrFail($id);
        $funcionario = User::findOrFail($sueldo->user_id);

        $sueldo->delete();

        return redirect()->route('backoffice.usuario-sueldo.index')->with('success','Sueldo de '.$funcionario->name.' actualizado');
    }
}
