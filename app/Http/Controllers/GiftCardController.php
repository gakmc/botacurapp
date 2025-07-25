<?php

namespace App\Http\Controllers;

use App\GiftCard;
use App\Programa;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Mail;
use App\Mail\GiftCardMailable;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\Snappy\Facades\SnappyPdf as sPDF;

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

        $codigo = $gc->codigo;
        $generator = new BarcodeGeneratorPNG();
        $barcode = base64_encode($generator->getBarcode($codigo, $generator::TYPE_CODE_128));

        $programa = Programa::with('servicios')->findOrFail($gc->id_programa);

        return view('themes.backoffice.pages.giftcard.show', compact('gc','programa', 'barcode'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $gc = GiftCard::findOrFail($id);

        $lista = ['botacura full','full day'];

        $programas = Programa::whereIn(strtolower('nombre_programa'), $lista)->get();

        return view('themes.backoffice.pages.giftcard.edit', compact('gc','programas'));
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
        $gc = GiftCard::findOrFail($id);

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

        dd($id, $request->all());

        GiftCard::create([
            'codigo' => $request->codigo,
            'monto' => $request->monto,
            'usada' => $gc->usada,
            'fecha_uso' => $gc->fecha_uso,
            'id_programa' => $request->id_programa,
            'id_venta' => $gc->id_venta,
            'validez_hasta' => $gc->validez_hasta,
            'de' => $request->de,
            'para' => $request->para,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'cantidad_personas' => $request->cantidad_personas,
            'generada_por' => $gc->generada_por,
        ]);

        return redirect()->route('backoffice.giftcards.index')->with('success','Gift Card actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $gc = GiftCard::findOrFail($id);

        $gc->delete();

        return redirect()->route('backoffice.giftcards.index')->with('success','Gift Card eliminada exitosamente.');
    }


    public function enviarpdf($id)
    {
        $gc = GiftCard::with('programa.servicios')->findOrFail($id);
        
        $generator = new BarcodeGeneratorPNG();
        $barcode = base64_encode($generator->getBarcode($gc->codigo, $generator::TYPE_CODE_128));
        
        $pdfData = sPDF::loadView('pdf.giftcard.viewPDF', [
            'gc' => $gc,
            'programa' => $gc->programa,
            'barcode' => $barcode
        ])->setOption('enable-local-file-access', true)
            ->setOption('disable-smart-shrinking', true)
            ->setPaper('a4')
            ->setOrientation('landscape')
            ->output();

        // return $pdfData->inline('GiftCard-'.$gc->id.'.pdf');
        
        Mail::to($gc->correo)
            ->send(new GiftCardMailable($gc, $pdfData));

        return redirect()->back()->with('success', 'Gift Card enviada correctamente a ' . $gc->correo);
    }

    
}
