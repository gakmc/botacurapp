<?php

namespace App\Http\Controllers;

use App\CategoriaCompra;
use App\Subcategoria;
use Illuminate\Http\Request;

class SubcategoriaController extends Controller
{
    public function create()
    {
        $categorias = CategoriaCompra::all();

        return view('themes.backoffice.pages.subcategoria.create', compact('categorias'));
    }

    public function edit($id)
    {
        $subcategoria = Subcategoria::findOrFail($id);
        $categorias = CategoriaCompra::all();

        return view('themes.backoffice.pages.subcategoria.edit', compact('subcategoria', 'categorias'));
    }

    public function getByCategoria($categoria_id)
    {
        $subcategorias = Subcategoria::where('categoria_id', $categoria_id)->get();
        return response()->json($subcategorias);
    }
}
