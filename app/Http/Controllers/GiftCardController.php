<?php

namespace App\Http\Controllers;

use App\GiftCard;
use App\Programa;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $giftcards = GiftCard::all();

        $mostrarUsadas = $request->input('usadas') === '1';

        $giftcards = GiftCard::where('usada', $mostrarUsadas)->get();

        // dd($giftcards);
        return view('themes.backoffice.pages.giftcard.index', compact('giftcards', 'mostrarUsadas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $lista = ['botacura full','full day'];

        $programas = Programa::whereIn(strtolower('nombre_programa'), $lista)->get();

        return view('themes.backoffice.pages.giftcard.create', compact('programas'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $numero = null;
        if ($request->has('telefono')) {
            $numero = str_replace('+','',$request->telefono);

            if (substr($numero,0,2) !== '56') {
                $numero = '56'.$numero;
            }

        }

        $request->merge([
            'monto' => (int) str_replace(['$','.',','],"",$request->monto),
            'telefono' => $numero
        ]);

        $codigo = GiftCard::generarCodigoUnicoGiftCard();


        $gc = GiftCard::create([
            'codigo' => $codigo,
            'monto' => $request->monto,
            'usada' => false,
            'fecha_uso' => null,
            'id_programa' => $request->id_programa,
            'id_venta' => null,
            'validez_hasta' => Carbon::now()->addDays(60)->toDateString(),
            'de' => $request->de,
            'para' => $request->para,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'cantidad_personas' => $request->cantidad_personas,
            'generada_por' => auth()->user()->id,
        ]);

        return redirect()->route('backoffice.giftcards.index')->with('success','Gift Card generada exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $gc = GiftCard::findOrFail($id);
        $programa = Programa::with('servicios')->findOrFail($gc->id_programa);

        return view('themes.backoffice.pages.giftcard.show', compact('gc','programa'));
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
