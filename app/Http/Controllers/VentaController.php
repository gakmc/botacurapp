<?php
namespace App\Http\Controllers;

use App\Asignacion;
use App\Consumo;
use App\DetalleConsumo;
use App\Jobs\ProcesarVentaCerrada;
use App\Mail\ConsumoMailable;
use App\Mail\VentaCerradaMailable;
use App\PagoConsumo;
use App\Propina;
use App\Reserva;
use App\TipoTransaccion;
use App\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
// use PDF;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
// use Barryvdh\Snappy\Facades\SnappyPdf;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\Input;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($reserva)
    {
        // $reserva = Reserva::findOrFail($reserva);
        // $tipos = TipoTransaccion::all();

        // return view('themes.backoffice.pages.venta.create', [
        //     'reserva' => $reserva,
        //     'tipos' => $tipos,
        // ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Reserva $reserva)
    {
        // dd($request);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function show(Venta $venta)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function edit(Venta $venta, Reserva $reserva)
    {
        $reserva->load('cliente', 'venta.tipoTransaccionAbono');
        $tipos = TipoTransaccion::all();

        // dd($reserva);

        return view('themes.backoffice.pages.venta.edit', [
            'reserva' => $reserva,
            'tipos'   => $tipos,
            'venta'   => $venta,
        ]);
    }

    public function cerrar(Reserva $reserva, Venta $ventum)
    {
        $reserva->load('cliente', 'venta.tipoTransaccionAbono');
        $tipos = TipoTransaccion::all();
        $ventum->load('consumo');

        if ($ventum->tiene_saldo_a_favor) {
            
            return view('themes.backoffice.pages.venta.cerrar_a_favor', [
                'reserva' => $reserva,
                'tipos'   => $tipos,
                'venta'   => $ventum,
            ]);

        }else {
            
            return view('themes.backoffice.pages.venta.cerrar', [
                'reserva' => $reserva,
                'tipos'   => $tipos,
                'venta'   => $ventum,
            ]);
        }

    }

    public function OLDcerrarventa(Request $request, Reserva $reserva, Venta $ventum)
    {
        
        $request->merge([
            'consumo_bruto'         => (int) str_replace(['$', '.', ','], '', $request->consumo_bruto),
            'propinaValue'        => (int) str_replace(['$', '.', ','], '', $request->propinaValue),
            'conPropina'       => (int) str_replace(['$', '.', ','], '', $request->conPropina),
            'servicio_bruto'       => (int) str_replace(['$', '.', ','], '', $request->servicio_bruto),
            'servicio_consumo'       => (int) str_replace(['$', '.', ','], '', $request->sinPropina),
            'pago1'       => (int) str_replace(['$', '.', ','], '', $request->pago1),
            'pago2'       => (int) str_replace(['$', '.', ','], '', $request->pago2),
            
            'diferencia'      => (int) str_replace(['$', '.', ','], '', $request->diferencia),
            'total_pagar'         => (int) str_replace(['$', '.', ','], '', $request->total_pagar),
        ]);

        // $request->validate([
        //     'propinaValue' => 'nullable|integer|min:0',
        //     'pago1'        => 'required_if:dividir_pago,on|integer|min:0',
        //     'pago2'        => 'required_if:dividir_pago,on|integer|min:0',
        //     // 'id_tipo_transaccion_diferencia' => 'nullable|exists:tipos_transacciones,id',
        //     // 'id_tipo_transaccion_diferencia_dividida' => 'nullable|exists:tipos_transacciones,id',
        //     // 'id_tipo_transaccion2' => 'nullable|exists:tipos_transacciones,id',

        //     // Al menos uno entre diferencia o dividida
        //     'id_tipo_transaccion_diferencia' => 'nullable|exists:tipos_transacciones,id|required_without:id_tipo_transaccion_diferencia_dividida',
        //     'id_tipo_transaccion_diferencia_dividida' => 'nullable|exists:tipos_transacciones,id|required_without:id_tipo_transaccion_diferencia',

        //     // Opcional solo en el caso dividido
        //     'id_tipo_transaccion2' => 'nullable|exists:tipos_transacciones,id',

        // ], [
        //     'id_tipo_transaccion_diferencia.required_without' => 'Debe indicar un tipo transacción diferencia.',
        //     'id_tipo_transaccion_diferencia_dividida.required_without' => 'Debe indicar un tipo de transacción dividida.',
        //     'pago1.required_if' => 'Cuando divide el pago, debe ingresar el primer monto.',
        //     'pago2.required_if' => 'Cuando divide el pago, debe ingresar el segundo monto.',
        // ]);

        $validator = Validator::make($request->all(), [
            'propinaValue' => 'nullable|integer|min:0',
            'pago1'        => 'required_if:dividir_pago,on|integer|min:0',
            'pago2'        => 'required_if:dividir_pago,on|integer|min:0',

            // Base: validaciones de existencia
            'id_tipo_transaccion_diferencia'          => 'nullable|exists:tipos_transacciones,id',
            'id_tipo_transaccion_diferencia_dividida' => 'nullable|exists:tipos_transacciones,id',
            'id_tipo_transaccion2'                    => 'nullable|exists:tipos_transacciones,id',

        ], [
            'pago1.required_if' => 'Cuando divide el pago, debe ingresar el primer monto.',
            'pago2.required_if' => 'Cuando divide el pago, debe ingresar el segundo monto.',
        ]);

        $validator->after(function ($v) use ($request) {
            $hasDif = $request->filled('id_tipo_transaccion_diferencia');
            $hasDiv = $request->filled('id_tipo_transaccion_diferencia_dividida');
            $hasT2  = $request->filled('id_tipo_transaccion2');

            // Caso válido A: solo diferencia
            $isOnlyDif = $hasDif && !$hasDiv && !$hasT2;

            // Caso válido B: ambos (dividida y t2)
            $isBothDiv = !$hasDif && $hasDiv && $hasT2;

            if (!($isOnlyDif || $isBothDiv)) {
                // Armar mensajes específicos según el error
                if ($hasDif && ($hasDiv || $hasT2)) {
                    $v->errors()->add('id_tipo_transaccion_diferencia',
                        'Si informa la transacción de diferencia, no debe informar las de pago dividido.');
                } elseif ($hasDiv && !$hasT2) {
                    $v->errors()->add('id_tipo_transaccion2',
                        'Cuando informa transacción dividida, también debe informar la segunda transacción.');
                } elseif ($hasT2 && !$hasDiv) {
                    $v->errors()->add('id_tipo_transaccion_diferencia_dividida',
                        'No puede informar la segunda transacción sin informar la transacción dividida.');
                } else {
                    // Ninguno vino: exigir uno de los dos casos válidos
                    $v->errors()->add('id_tipo_transaccion_diferencia',
                        'Debe informar la transacción de diferencia o bien ambas del pago dividido.');
                    $v->errors()->add('id_tipo_transaccion_diferencia_dividida',
                        'Debe informar la transacción de diferencia o bien ambas del pago dividido.');
                }
            }
        });

        $validator->validate();
   
        $venta       = $ventum;
        $consumo     = $venta->consumo;
        $cliente     = $reserva->cliente->nombre_cliente;
        $pagoConsumo = null;
        
        try {
            

            DB::transaction(function () use ($request, &$venta, $reserva, $consumo, &$pagoConsumo) {
                
                // Modificar los valores en la tabla venta
                $venta->diferencia_programa = $request->input('diferencia');
                $venta->total_pagar = 0;
                $venta->id_tipo_transaccion_diferencia = ($request->has('id_tipo_transaccion_diferencia') ? $request->id_tipo_transaccion_diferencia : $request->id_tipo_transaccion_diferencia_dividida);


                $pagoConsumo = PagoConsumo::create([
                    "valor_consumo" => $request->total_pagar,
                    "pago1"         => 0,
                    "pago2"         => 0,
                    "imagen_pago1"  => null,
                    "imagen_pago2"  => null,
                    "id_venta"    => $venta->id,
                    "id_tipo_transaccion1" => ($request->has('id_tipo_transaccion_diferencia') ? $request->id_tipo_transaccion_diferencia : $request->id_tipo_transaccion_diferencia_dividida),
                    "id_tipo_transaccion2" => null,

                ]);

                if ($request->has('dividir_pago')) {


                    if ($request->hasFile('imagen_diferencia_dividida')) {

                        //* Se cambio el almacenamiento por un metodo mas limpio
                        $ruta = $this->guardarImagenYObtenerRuta($request->file('imagen_diferencia_dividida'));
                        $venta->folio_diferencia = $request->input('folio_diferencia') ?? null;
                        $pagoConsumo->imagen_pago1 = $ruta;
        
                    }


                    if ($request->hasFile('imagen_pago2')) {

                        $ruta = $this->guardarImagenYObtenerRuta($request->file('imagen_pago2'));
                        $pagoConsumo->imagen_pago2 = $ruta;

                    }
        
        
                    if ($request->has('id_tipo_transaccion2')) {
                        $pagoConsumo->id_tipo_transaccion2 = $request->input('id_tipo_transaccion2');
                    }

                    $pagoConsumo->pago1 = $request->input('pago1');
                    $pagoConsumo->pago2 = $request->input('pago2');

                }else {

                    if ($request->has('folio_diferencia')) {

                        $venta->folio_diferencia = $request->input('folio_diferencia');
                        $pagoConsumo->imagen_pago1 = null;
        
                    }


                    $pagoConsumo->pago1 = $request->input('total_pagar');

                }



                //* Guarda los cambios
                $pagoConsumo->save();
                $venta->save();

                $totalPropinas = null;
                
                //* Si la venta tiene consumo
                if (!is_null($consumo)) {
                    if ($consumo->detallesConsumos->isEmpty()) {
                        throw new \Exception('No se puede generar propina porque no hay consumo registrado o equipo asignado en esta venta.');

                    }

                    if ($request->has('propina')) {
                        $totalPropinas = $this->asignarPropinas($consumo, $reserva, $request);
                    } else {      
                        DetalleConsumo::where('id_consumo', $consumo->id)->update(['genera_propina' => 0]);
                    }
                    
                }


            });


            $total   = 0;
            $propina = 'No Aplica';
            $menus     = $reserva->menus;
            $idConsumo = null;
            $diferencia = $request->diferencia;



            if (is_null($consumo)) {
                $propina = 'No Aplica';
                $cantidadPropina = 'No Aplica';
            } else {
                $idConsumo = $consumo->id;

                $tienePropina = $consumo->detallesConsumos->contains('genera_propina', 1);
                
                if ($tienePropina && $request->has('propina')) {
                    $propina = 'Si';
                    $total = $consumo->total_consumo;

                    $propinaModel = Propina::where('propinable_id', $idConsumo)
                        ->where('propinable_type', Consumo::class)
                        ->first();

                    $cantidadPropina = $propinaModel ? $propinaModel->cantidad : 'No Aplica';
                } else {
                    $propina = 'No';
                    $total = $consumo->subtotal;
                    $cantidadPropina = 'No Aplica';
                }
            }


            //* Carga la data para enviarla al PDF
            $data = [
                'nombre'        => $reserva->cliente->nombre_cliente,
                'numero'        => $reserva->cliente->whatsapp_cliente,
                'observacion'   => $reserva->observacion ? $reserva->observacion : 'Sin Observaciones',
                'fecha_visita'  => $reserva->fecha_visita,
                'programa'      => $reserva->programa->nombre_programa,
                'personas'      => $reserva->cantidad_personas,
                'menus'         => $menus,
                'consumo'       => $consumo,
                'pagoConsumo'   => $pagoConsumo,
                'venta'         => $venta,
                'total'         => $total,
                'propina'       => $propina,
                'propinaPagada' => isset($cantidadPropina) && $cantidadPropina !== null ? $cantidadPropina : 'No Aplica',
                'diferencia' => $diferencia,
                'correo' => $reserva->cliente->correo,
            ];

            //* Generar el PDF
            $this->generarYEnviarPDF(
                'pdf.venta.viewPDF',
                $data,
                'Detalle_Venta',
                $reserva->cliente->nombre_cliente,
                $reserva->fecha_visita,
                VentaCerradaMailable::class

            );

            //? Si se genera la separación de consumo con venta
            if ($request->has('dividir_pago')) {

                //* Carga la data para enviarla al PDF
                $dataConsumo = [
                    'nombre'        => $reserva->cliente->nombre_cliente,
                    'numero'        => $reserva->cliente->whatsapp_cliente,
                    'observacion'   => $reserva->observacion ? $reserva->observacion : 'Sin Observaciones',
                    'fecha_visita'  => $reserva->fecha_visita,
                    'programa'      => $reserva->programa->nombre_programa,
                    'personas'      => $reserva->cantidad_personas,
                    'consumo'       => $consumo,
                    'pagoConsumo'   => $pagoConsumo,
                    'venta'         => $venta,
                    'total'         => $total,
                    'propina'       => $propina,
                    'propinaPagada' => $cantidadPropina ?? 'No Aplica',
                    'correo' => $reserva->cliente->correo,
                ];
                // Log::info('Enviando correo de consumo', $dataConsumo);

                //* Generar el PDF
                try {
                    $this->generarYEnviarPDF(
                        'pdf.consumo_separado.viewPDF',
                        $dataConsumo,
                        'Detalle_Consumo',
                        $reserva->cliente->nombre_cliente,
                        $reserva->fecha_visita,
                        ConsumoMailable::class
                    );
                } catch (\Throwable $e) {
                    Log::error('Fallo al enviar correo de consumo: ' . $e->getMessage());
                    // session()->flash('error', 'No se pudo enviar el correo con el detalle del consumo.');
                }


            }

            Alert::success('Éxito', 'Venta para ' . $cliente . ' cerrada con éxito', 'Confirmar')->showConfirmButton();
            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id]);
            
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
    
    
    public function cerrarventa(Request $request, Reserva $reserva, Venta $ventum)
    {
        $inicioDebug = microtime(true);
        // Normalizar montos que vienen como $1.234.567
        $request->merge([
            'consumo_bruto'   => (int) str_replace(['$', '.', ','], '', $request->consumo_bruto),
            'propinaValue'    => (int) str_replace(['$', '.', ','], '', $request->propinaValue),
            'conPropina'      => (int) str_replace(['$', '.', ','], '', $request->conPropina),
            'servicio_bruto'  => (int) str_replace(['$', '.', ','], '', $request->servicio_bruto),
            'servicio_consumo'=> (int) str_replace(['$', '.', ','], '', $request->sinPropina),
            'pago1'           => (int) str_replace(['$', '.', ','], '', $request->pago1),
            'pago2'           => (int) str_replace(['$', '.', ','], '', $request->pago2),
            'diferencia'      => (int) str_replace(['$', '.', ','], '', $request->diferencia),
            'total_pagar'     => (int) str_replace(['$', '.', ','], '', $request->total_pagar),
        ]);
        
        // Validación
        $validator = Validator::make($request->all(), [
            'propinaValue' => 'nullable|integer|min:0',
            'pago1'        => 'required_if:dividir_pago,on|integer|min:0',
            'pago2'        => 'required_if:dividir_pago,on|integer|min:0',
            
            // existencia en BD
            'id_tipo_transaccion_diferencia'          => 'nullable|exists:tipos_transacciones,id',
            'id_tipo_transaccion_diferencia_dividida' => 'nullable|exists:tipos_transacciones,id',
            'id_tipo_transaccion2'                    => 'nullable|exists:tipos_transacciones,id',
        ], [
            'pago1.required_if' => 'Cuando divide el pago, debe ingresar el primer monto.',
            'pago2.required_if' => 'Cuando divide el pago, debe ingresar el segundo monto.',
        ]);

        // Validación lógica de combinaciones de medios de pago
        $validator->after(function ($v) use ($request) {
            $hasDif = $request->filled('id_tipo_transaccion_diferencia');
            $hasDiv = $request->filled('id_tipo_transaccion_diferencia_dividida');
            $hasT2  = $request->filled('id_tipo_transaccion2');

            // Caso válido A: solo diferencia
            $isOnlyDif = $hasDif && !$hasDiv && !$hasT2;

            // Caso válido B: dividido (id_tipo_transaccion_diferencia_dividida + id_tipo_transaccion2)
            $isBothDiv = !$hasDif && $hasDiv && $hasT2;

            if (!($isOnlyDif || $isBothDiv)) {

                if ($hasDif && ($hasDiv || $hasT2)) {
                    $v->errors()->add(
                        'id_tipo_transaccion_diferencia',
                        'Si informa la transacción de diferencia, no debe informar las de pago dividido.'
                    );
                } elseif ($hasDiv && !$hasT2) {
                    $v->errors()->add(
                        'id_tipo_transaccion2',
                        'Cuando informa transacción dividida, también debe informar la segunda transacción.'
                    );
                } elseif ($hasT2 && !$hasDiv) {
                    $v->errors()->add(
                        'id_tipo_transaccion_diferencia_dividida',
                        'No puede informar la segunda transacción sin informar la transacción dividida.'
                    );
                } else {
                    // Ninguno vino: exigir uno de los dos casos válidos
                    $v->errors()->add(
                        'id_tipo_transaccion_diferencia',
                        'Debe informar la transacción de diferencia o bien ambas del pago dividido.'
                    );
                    $v->errors()->add(
                        'id_tipo_transaccion_diferencia_dividida',
                        'Debe informar la transacción de diferencia o bien ambas del pago dividido.'
                    );
                }
            }
        });

        $validator->validate();

        $venta   = $ventum;
        $consumo = $venta->consumo;
        $cliente = $reserva->cliente->nombre_cliente;

        try {

            DB::transaction(function () use ($request, $reserva, &$venta, $consumo) {

                // 1) Actualizar venta (saldar diferencia)
                $venta->diferencia_programa = $request->input('diferencia');
                $venta->total_pagar         = 0;
                $venta->id_tipo_transaccion_diferencia = $request->has('id_tipo_transaccion_diferencia')
                    ? $request->id_tipo_transaccion_diferencia
                    : $request->id_tipo_transaccion_diferencia_dividida;

                // 2) Crear registro de pago de consumo base
                $pagoConsumo = PagoConsumo::create([
                    'valor_consumo'          => $request->total_pagar,
                    'pago1'                  => 0,
                    'pago2'                  => 0,
                    'imagen_pago1'           => null,
                    'imagen_pago2'           => null,
                    'id_venta'               => $venta->id,
                    'id_tipo_transaccion1'   => $venta->id_tipo_transaccion_diferencia,
                    'id_tipo_transaccion2'   => null,
                ]);

                // 3) Manejo de división de pago
                if ($request->has('dividir_pago')) {

                    // imagen diferencia dividida
                    if ($request->hasFile('imagen_diferencia_dividida')) {
                        $ruta = $this->guardarImagenYObtenerRuta($request->file('imagen_diferencia_dividida'));
                        $venta->folio_diferencia   = $request->input('folio_diferencia') ?? null;
                        $pagoConsumo->imagen_pago1 = $ruta;
                    }

                    // imagen pago2
                    if ($request->hasFile('imagen_pago2')) {
                        $ruta = $this->guardarImagenYObtenerRuta($request->file('imagen_pago2'));
                        $pagoConsumo->imagen_pago2 = $ruta;
                    }

                    if ($request->has('id_tipo_transaccion2')) {
                        $pagoConsumo->id_tipo_transaccion2 = $request->input('id_tipo_transaccion2');
                    }

                    $pagoConsumo->pago1 = $request->input('pago1');
                    $pagoConsumo->pago2 = $request->input('pago2');

                } else {

                    // Pago no dividido: sólo se informa folio y se deja todo en pago1
                    if ($request->has('folio_diferencia')) {
                        $venta->folio_diferencia   = $request->input('folio_diferencia');
                        $pagoConsumo->imagen_pago1 = null;
                    }

                    $pagoConsumo->pago1 = $request->input('total_pagar');
                }

                $pagoConsumo->save();
                $venta->save();

                // 4) Propinas (si hay consumo)
                if (!is_null($consumo)) {

                    if ($consumo->detallesConsumos->isEmpty()) {
                        throw new \Exception('No se puede generar propina porque no hay consumo registrado o equipo asignado en esta venta.');
                    }

                    if ($request->has('propina')) {
                        // tu lógica actual de asignar propinas
                        $this->asignarPropinas($consumo, $reserva, $request);
                    } else {
                        // marcar que no generan propina
                        DetalleConsumo::where('id_consumo', $consumo->id)
                            ->update(['genera_propina' => 0]);
                    }
                }

            }); // fin transaction

            // === Después del commit: nada pesado de BD ===
            // Aquí sólo preparamos lo justo para el Job

            $venta->refresh();
            $pagoConsumo = $venta->pagoConsumo ?? $venta->pagoConsumos()->latest()->first(); // según tu relación
            $consumo     = $venta->consumo; // recargar por seguridad

            $idConsumo = $consumo ? $consumo->id : null;
            $tienePropina = false;
            $total   = 0;
            $propina = 'No Aplica';
            $cantidadPropina = 'No Aplica';

            if ($consumo) {
                $tienePropina = $consumo->detallesConsumos->contains('genera_propina', 1);

                if ($tienePropina && $request->has('propina')) {
                    $propina = 'Si';
                    $total   = $consumo->total_consumo;

                    $propinaModel = Propina::where('propinable_id', $idConsumo)
                        ->where('propinable_type', Consumo::class)
                        ->first();

                    $cantidadPropina = $propinaModel ? $propinaModel->cantidad : 'No Aplica';
                } else {
                    $propina = $tienePropina ? 'No' : 'No Aplica';
                    $total   = $consumo->subtotal;
                }
            }

            // Payload mínimo para el Job de PDF + correos
            $payload = [
                'reserva_id'      => $reserva->id,
                'venta_id'        => $venta->id,
                'pago_consumo_id' => $pagoConsumo ? $pagoConsumo->id : null,
                'total'           => $total,
                'propina'         => $propina,
                'propinaPagada'   => $cantidadPropina,
                'diferencia'      => $request->diferencia,
                'enviarConsumo'   => $request->has('dividir_pago'),
            ];

            // 🔥 Aquí ya NO generamos PDF ni enviamos correos directamente
            ProcesarVentaCerrada::dispatch($payload);

            Alert::success('Éxito', 'Venta para ' . $cliente . ' cerrada con éxito', 'Confirmar')
                ->showConfirmButton();

                
        
            $finDebug = microtime(true);
            Log::info("Tiempo controlador Cierre de ventas (/reservas/cerrarVenta): ".round($finDebug - $inicioDebug, 3)." s");

            return redirect()->route('backoffice.reserva.show', ['reserva' => $reserva->id]);

        } catch (\Exception $e) {
            Log::error('Error al cerrar venta: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, Venta $venta)
    {
        //
    }

    public function destroy(Venta $venta)
    {
        //
    }

    public function index_cierre()
    {
        // Asignacion de dias Hoy y Mañana
        $hoy    = Carbon::today();
        
        // Filtrar las reservas que tienen visitas y cuya fecha de visita es hoy o mañana
        $reservas = Reserva::with('visitas', 'cliente', 'programa', 'user', 'venta.consumo.detallesConsumos.producto', 'venta.consumo.detalleServiciosExtra.servicio')
        ->where('fecha_visita', $hoy)
        ->get();

        $asignados = Asignacion::all();

        // $detalles = collect();

        // foreach($reservas as $reserva)
        // {
        //     foreach($reserva->venta->consumos as $consumo){
        //         $detalles = $detalles->merge($consumo->detallesConsumos);
        //     }
        // }

        return view('themes.backoffice.pages.venta.cierre.index_cierre',[
            'reservas' => $reservas,
            'asignados' => $asignados,
            // 'detalles' => $detalles
        ]);
    }

    private function guardarImagenYObtenerRuta($archivo, $disk = 'imagen_diferencia')
    {
        $nombreArchivo = Carbon::now()->format('Ymd_His') . '-' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $rutaTemporal = 'temp/' . $nombreArchivo;

        Storage::disk($disk)->put($rutaTemporal, File::get($archivo));

        $rutaFinal = '/' . $nombreArchivo;
        Storage::disk($disk)->move($rutaTemporal, $rutaFinal);

        return $rutaFinal;
    }

    private function asignarPropinas(Consumo $consumo, Reserva $reserva, Request $request)
    {

        $fecha = Carbon::createFromFormat('d-m-Y', $reserva->fecha_visita)->format('Y-m-d');
        $totalPropinas = $request->propinaValue;

        $propina = $consumo->propina()->create([
            'fecha'    => $fecha,
            'cantidad' => $totalPropinas,
        ]);

        $asignacion = Asignacion::where('fecha', $fecha)->first();

        if ($asignacion && $asignacion->users->count() > 0) {
            foreach ($asignacion->users as $user) {
                DB::table('propina_user')->insert([
                    'id_user'        => $user->id,
                    'id_propina'     => $propina->id,
                    'monto_asignado' => $totalPropinas / count($asignacion->users),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        return $totalPropinas;
    }

    private function generarYEnviarPDF($view, $data, $nombreBase, $clienteNombre, $fecha, $mailClass)
    {
        $nombreArchivo = $nombreBase . '_' . Str::slug($clienteNombre) . '_' . $fecha . '.pdf';
        $rutaPDF = storage_path('app/public/') . $nombreArchivo;

        $pdf = Pdf::loadView($view, $data);
        $pdf->save($rutaPDF);

        $data['pdfPath'] = $rutaPDF;

        Mail::to($data['correo'])->send(new $mailClass($data, $rutaPDF));
    }

}
