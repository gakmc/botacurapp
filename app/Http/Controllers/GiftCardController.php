<?php
namespace App\Http\Controllers;

use App\Cliente;
use App\GiftCard;
use App\Mail\GiftCardMailable;
use App\Programa;
use Barryvdh\Snappy\Facades\SnappyPdf as sPDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Picqer\Barcode\BarcodeGeneratorPNG;

class GiftCardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Vista con fechas de compra y vencimiento, filtros y resumen (15/10/2026)
        $ver = $request->input('ver', $request->input('usadas') === '1' ? 'usadas' : 'sin_usar');
        if (! in_array($ver, ['sin_usar', 'por_vencer', 'vencidas', 'usadas', 'todas'], true)) {
            $ver = 'sin_usar';
        }
        $mostrarUsadas = $ver === 'usadas';

        $todas = GiftCard::with(['programa', 'venta.reserva'])->get();
        $sinUsar = $todas->where('usada', false);
        $resumen = [
            'vigentes'       => $sinUsar->filter(function ($g) { return $g->dias_restantes !== null && $g->dias_restantes >= 0; })->count(),
            'por_vencer'     => $sinUsar->filter(function ($g) { return $g->dias_restantes !== null && $g->dias_restantes >= 0 && $g->dias_restantes <= 7; })->count(),
            'vencidas'       => $sinUsar->filter(function ($g) { return $g->dias_restantes !== null && $g->dias_restantes < 0; })->count(),
            'usadas'         => $todas->where('usada', true)->count(),
            'monto_vigente'  => (int) $sinUsar->filter(function ($g) { return $g->dias_restantes !== null && $g->dias_restantes >= 0; })->sum('monto'),
            'personas_vigente' => (int) $sinUsar->filter(function ($g) { return $g->dias_restantes !== null && $g->dias_restantes >= 0; })->sum('cantidad_personas'),
        ];

        switch ($ver) {
            case 'usadas':
                $giftcards = $todas->where('usada', true)->sortByDesc(function ($g) { return $g->fecha_uso ?: $g->updated_at; });
                break;
            case 'vencidas':
                $giftcards = $sinUsar->filter(function ($g) { return $g->dias_restantes !== null && $g->dias_restantes < 0; })->sortByDesc('dias_restantes');
                break;
            case 'por_vencer':
                $giftcards = $sinUsar->filter(function ($g) { return $g->dias_restantes !== null && $g->dias_restantes >= 0 && $g->dias_restantes <= 7; })->sortBy('dias_restantes');
                break;
            case 'todas':
                $giftcards = $todas->sortByDesc(function ($g) { return optional($g->fecha_compra_real)->timestamp; });
                break;
            default:
                $giftcards = $sinUsar->filter(function ($g) { return $g->dias_restantes === null || $g->dias_restantes >= 0; })->sortBy('dias_restantes');
        }
        $giftcards = $giftcards->values();

        return view('themes.backoffice.pages.giftcard.index', compact('giftcards', 'mostrarUsadas', 'ver', 'resumen'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // $lista = ['botacura full', 'full day', 'Caviahue 2'];

        // $programas = Programa::activos()->whereIn(strtolower('nombre_programa'), $lista)->get();

        $programas = Programa::permiteGc()->activos()->get();
        // dd($programas);

        $fechaManual = config('woocommerce.gift_card_fecha_manual');

        return view('themes.backoffice.pages.giftcard.create', compact('programas', 'fechaManual'));
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
            $numero = str_replace('+', '', $request->telefono);

            if (substr($numero, 0, 2) !== '56') {
                $numero = '56' . $numero;
            }

        }

        $request->merge([
            'monto'    => (int) str_replace(['$', '.', ','], "", $request->monto),
            'telefono' => $numero,
        ]);

        $fechaManual = config('woocommerce.gift_card_fecha_manual');

        if ($fechaManual) {
            $request->validate([
                'fecha_uso'     => 'required|date',
                'validez_hasta' => 'nullable|date',
            ]);
        }

        $codigo = GiftCard::generarCodigoUnicoGiftCard();

        $fechaUso     = $fechaManual ? $request->fecha_uso : null;
        // Vigencia: 45 dias desde la fecha de compra (15/10/2026)
        $request->validate(['fecha_compra' => 'nullable|date']);
        $fechaCompra  = $request->filled('fecha_compra') ? Carbon::parse($request->fecha_compra)->toDateString() : Carbon::today()->toDateString();
        $validezHasta = GiftCard::venceDesde($fechaCompra);

        $gc = GiftCard::create([
            'codigo'            => $codigo,
            'monto'             => $request->monto,
            'usada'             => false,
            'fecha_uso'         => $fechaUso,
            'id_programa'       => $request->id_programa,
            'id_venta'          => null,
            'validez_hasta'     => $validezHasta,
            'fecha_compra'      => $fechaCompra,
            'de'                => $request->de,
            'para'              => $request->para,
            'correo'            => $request->correo,
            'telefono'          => $request->telefono,
            'cantidad_personas' => $request->cantidad_personas,
            'generada_por'      => auth()->user()->id,
        ]);

        return redirect()->route('backoffice.giftcards.index')->with('success', 'Gift Card generada exitosamente.');
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

        $codigo    = $gc->codigo;
        $generator = new BarcodeGeneratorPNG();
        $barcode   = base64_encode($generator->getBarcode($codigo, $generator::TYPE_CODE_128));

        $programa = Programa::with('servicios')->findOrFail($gc->id_programa);

        return view('themes.backoffice.pages.giftcard.show', compact('gc', 'programa', 'barcode'));
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

        $lista = ['botacura full', 'full day'];

        $programas = Programa::whereIn(strtolower('nombre_programa'), $lista)->get();

        return view('themes.backoffice.pages.giftcard.edit', compact('gc', 'programas'));
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
            $numero = str_replace('+', '', $request->telefono);

            if (substr($numero, 0, 2) !== '56') {
                $numero = '56' . $numero;
            }

        }

        $request->merge([
            'monto'    => (int) str_replace(['$', '.', ','], "", $request->monto),
            'telefono' => $numero,
        ]);

        // dd($id, $request->all());

        $gc->update([
            'codigo'            => $request->codigo,
            'monto'             => $request->monto,
            'usada'             => $gc->usada,
            'fecha_uso'         => $request->filled('fecha_uso') ? $request->fecha_uso : $gc->fecha_uso,
            'id_programa'       => $request->id_programa,
            'id_venta'          => $gc->id_venta,
            'fecha_compra'      => $fechaCompraUpd = ($request->filled('fecha_compra') ? Carbon::parse($request->fecha_compra)->toDateString() : optional($gc->fecha_compra_real)->toDateString()),
            'validez_hasta'     => $fechaCompraUpd ? GiftCard::venceDesde($fechaCompraUpd) : $gc->validez_hasta,
            'de'                => $request->de,
            'para'              => $request->para,
            'correo'            => $request->correo,
            'telefono'          => $request->telefono,
            'cantidad_personas' => $request->cantidad_personas,
            'generada_por'      => $gc->generada_por,
        ]);

        return redirect()->route('backoffice.giftcards.index')->with('success', 'Gift Card actualizada exitosamente.');
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

        return redirect()->route('backoffice.giftcards.index')->with('success', 'Gift Card eliminada exitosamente.');
    }

    /**
     * wkhtmltopdf sin "patched qt" ignora disable-smart-shrinking y achica la página a ~77 %:
     * se compensa con zoom para que el diseño llene la hoja completa.
     */
    private static function zoomPdf(): float
    {
        static $zoom = null;
        if ($zoom === null) {
            $bin = config('snappy.pdf.binary');
            $ver = function_exists('exec') ? @exec(escapeshellarg($bin) . ' --version 2>&1') : '';
            $zoom = (stripos((string) $ver, 'patched qt') !== false) ? 1.0 : 1.3;
        }
        return $zoom;
    }

    /** PDF de la gift card (diseño "Valle", A4 apaisado sin márgenes). */
    private function pdfDe(GiftCard $gc)
    {
        $generator = new BarcodeGeneratorPNG();
        $barcode   = base64_encode($generator->getBarcode($gc->codigo, $generator::TYPE_CODE_128, 2, 60));

        return sPDF::loadView('pdf.giftcard.viewPDF', [
            'gc'       => $gc,
            'programa' => $gc->programa,
            'barcode'  => $barcode,
            'zoom'     => self::zoomPdf(),
        ])->setOption('enable-local-file-access', true)
            ->setOption('disable-smart-shrinking', true)
            ->setOption('margin-top', 0)->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)->setOption('margin-right', 0)
            ->setPaper('a4')
            ->setOrientation('landscape');
    }

    /** Ver el PDF en el navegador antes de enviarlo. */
    public function verpdf($id)
    {
        $gc = GiftCard::with('programa.servicios')->findOrFail($id);
        return $this->pdfDe($gc)->inline('GiftCard-' . $gc->codigo . '.pdf');
    }

    public function enviarpdf($id)
    {
        $gc = GiftCard::with('programa.servicios')->findOrFail($id);
        $pdfData = $this->pdfDe($gc)->output();

        Mail::to($gc->correo)
            ->queue(new GiftCardMailable($gc, $pdfData));

        return redirect()->back()->with('success', 'Gift Card enviada correctamente a ' . $gc->correo);
    }

    public function byPassReserva($id)
    {
        $gc      = GiftCard::findOrFail($id);
        $cliente = Cliente::where('nombre_cliente', $gc->para)->first();
        // dd($gc, $cliente);
        // dd($cliente->exists());
        if ($cliente) {
            return redirect()->route('backoffice.reserva.create', [
                'cliente'  => $cliente,
                'programa' => $gc->id_programa,
                'cantidad' => $gc->cantidad_personas,
                'monto'    => $gc->monto,
                'codigo'   => $gc->codigo,

            ]);
        } else {
            return redirect()->route('backoffice.cliente.create', ['nombre' => $gc->para]);
        }
    }

    public function verificarCodigo(Request $request)
    {
        $codigo = $request->codigo;

        $gc = GiftCard::where('codigo', $codigo)
            ->where('usada', false)
            ->whereNull('id_venta')
            ->first();

        if ($gc) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'id'       => $gc->id,
                    'programa' => $gc->id_programa,
                    'cantidad' => $gc->cantidad_personas,
                    'monto'    => $gc->monto,
                    'codigo'   => $gc->codigo,
                ],
            ]);
        }

        return response()->json(['success' => false]);
    }

    public function listaCodigos()
    {
        $codigos = GiftCard::where('usada', false)
            ->whereNull('id_venta')
            ->pluck('codigo');

        // Formato para Materialize: {"CODIGO1": null, "CODIGO2": null}
        $data = [];
        foreach ($codigos as $codigo) {
            $data[$codigo] = null;
        }

        return response()->json($data);
    }

}
