@extends('themes.backoffice.layouts.admin')

@section('title')
Resumen {{ucfirst(\Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM'))}}
@endsection

@section('content')
<div class="section">
              <p class="caption">Resumen Mensual - {{ ucfirst($mesNombre) }} {{ $anio }}.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m10 offset-m1 ">
                    <div class="card-panel">
<div class="container">





    
    <a class="btn waves-effect modal-trigger" href="#modal-comparar">
  <i class="material-icons left">timeline</i> Comparar ingresos
</a>

<div id="modal-comparar" class="modal modal-fixed-footer">
  <div class="modal-content">
    <h5 class="grey-text text-darken-2">Comparar ingresos</h5>

    <div class="row" style="margin-bottom:0;">
      <div class="input-field col s12 m8">
        <select multiple id="meses_comparar">
          @foreach($fechasDisponibles as $f)
            @php
              $mm=(int)$f->mes; $yy=(int)$f->anio; $val=$mm.'-'.$yy;
              $isActual = ($mes.'-'.$anio) === $val;
              $mesNombre = ucfirst(\Carbon\Carbon::create()->month($mm)->locale('es')->isoFormat('MMMM'));
            @endphp
            <option value="{{ $val }}" {{ $isActual ? 'selected disabled' : '' }}>
              {{ $mesNombre }} {{ $yy }} {{ $isActual ? '(mes actual)' : '' }}
            </option>
          @endforeach
        </select>
        <label>Selecciona hasta 3 meses adicionales</label>
      </div>
      <div class="input-field col s12 m4">
        <button id="btn-comparar" class="btn waves-effect" style="width:100%;">
          <i class="material-icons left">compare_arrows</i> Generar comparativa
        </button>
      </div>
    </div>

    <div id="resultadoComparativa" style="min-height:120px;">
      <p class="grey-text">Selecciona meses y presiona “Generar comparativa”.</p>
    </div>
  </div>
  <div class="modal-footer">
    <a href="#!" class="modal-close waves-effect btn-flat">Cerrar</a>
  </div>
</div>




    

    <div class="row">
  <div class="input-field col s12 m6 offset-m3">
    <select name="mes_anio" id="mes_anio">
      @foreach($fechasDisponibles as $fecha)
        @php
          $value = $fecha->mes . '-' . $fecha->anio;
          $mesNombreIter = ucfirst(\Carbon\Carbon::create()->month($fecha->mes)->locale('es')->isoFormat('MMMM'));
        @endphp
        <option value="{{ $value }}" {{ ($mes . '-' . $anio) == $value ? 'selected' : '' }}>
          {{ $mesNombreIter }} {{ $fecha->anio }}
        </option>
      @endforeach
    </select>
    <label for="mes_anio">Selecciona Mes y Año</label>
  </div>
</div>



    <h4 class="center-align">Reportes por Semana</h4>

    @php
        // Agrupar por semana (yearweek)
        $semanas = collect($abonos)->pluck('yearweek')
            ->merge(collect($diferencias)->pluck('yearweek'))
            ->merge(collect($consumos)->pluck('yearweek'))
            ->merge(collect($servicios)->pluck('yearweek'))
            ->merge(collect($ventasDirectas)->pluck('yearweek'))
            ->unique()->sort();
    @endphp

    @php
        $ingresoMesData = 0;
        $egresoMesData = 0;
        $totalMesData = 0;
    @endphp

    @foreach($semanas as $week)
        @php
            // Obtener fechas dentro de esa semana
            $fechas = collect($abonos)
                ->merge($diferencias)
                ->merge($consumos)
                ->merge($servicios)
                ->merge($ventasDirectas)
                ->where('yearweek', $week)
                ->pluck('fecha')
                ->map(function($f) {
                    return \Carbon\Carbon::parse($f)->toDateString(); // <-- aquí
                })
                ->unique()
                ->sort();


                $fechaCarbon = \Carbon\Carbon::parse($fechas->first());
                $inicio = $fechaCarbon->startOfWeek()->translatedFormat('d M');
                $fin    = $fechaCarbon->endOfWeek()->translatedFormat('d M');
        @endphp

                @php
                    $ingresosData = 0;
                @endphp

        <h5 class="teal-text">Semana {{ $inicio }} - {{ $fin }}</h5>

        <table class="striped responsive-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Abonos</th>
                    <th>Diferencias</th>
                    <th>Consumos y ventas directas</th>
                    <th>Servicios</th>
                </tr>
            </thead>
            <tbody>


                @foreach($fechas as $fecha)

                @php
                    $ingresosData += (optional($abonos->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($diferencias->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($consumos->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($ventasDirectas->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($servicios->firstWhere('fecha', $fecha))->total ?? 0);


                @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</td>
                        <td>${{ number_format(optional($abonos->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($diferencias->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($consumos->firstWhere('fecha', $fecha))->total + optional($ventasDirectas->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($servicios->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>

                    </tr>
                @endforeach
                @php
                   $ingresoMesData += $ingresosData;
                @endphp
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"></td>

                    <td><strong>Total Semana: ${{ number_format($ingresosData, 0, ',', '.') }}</strong></td>
                </tr>
            </tfoot>
        </table>
        <br>

    @endforeach
</div>


            <h5 class="right-align">Total Mensual:</h5>

                <div class="row" style="display: flex">
                  <div class="col s10 m3" style="justify-content:center; align-content">
                    <div class="card-panel gradient-shadow center-align" style="background-color: #039B7B;">
                      <span class="white-text"><strong>Ingresos Totales: </strong>${{ number_format($ingresoMesData, 0, ',', '.') }}</span>
                    </div>
                  </div>
                </div>

                    </div>
                </div>
                </div>
                </div>













                                @php
                                    $totalConsumo = 0;
                                    $totalServicios = 0;
                                    $diferenciasAcumuladas = 0;
                                @endphp

                                @foreach ($ventas as $venta)
                                    @php
                                        $consumoSinPropina = 0;
                                        $serviciosSinPropina = 0;
                                        $diferencia = 0;
                                        $isGiftCard = $venta->pagado_con_giftcard ?? false;

                                        if (!$isGiftCard) {
                                            $totalDiferencia = $venta->pendiente_de_pago ? $venta->total_pagar : $venta->diferencia_programa;
                                            $diferenciasAcumuladas += $totalDiferencia;
                                            $diferencia = $totalDiferencia;
                                        }

                                        if ($venta->consumo != null) {
                                            $consumoSinPropina = $venta->consumo->detallesConsumos->sum("subtotal");  
                                            $serviciosSinPropina = $venta->consumo->detalleServiciosExtra->sum("subtotal");
                                        }

                                        $totalConsumo += $consumoSinPropina;
                                        $totalServicios += $serviciosSinPropina;
                                    @endphp

                                @endforeach




                {{-- Resumenes --}}
                <div class="card-panel">
                    <div class="row">

                        {{-- Resumen de programas --}}
                        <div class="col s12 m3">
                            <h5><strong>Resumen de programas</strong></h5>
                            <table class="striped centered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Programa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($programas as $programa)
                                        @php
                                            $contratado = ($programa->total_programas == 0 || $programa->total_programas > 1) ? 'contratados' : 'contratado';
                                        @endphp
                                        <tr>
                                            <td>{{ $programa->nombre_programa }}: {{ $programa->total_programas }} {{ $contratado }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Resumen de transacciones --}}
                        <div class="col s12 m3">
                            <h5><strong>Resumen de transacciones</strong></h5>
                            <table class="striped bordered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Tipo Transaccion</th>
                                        <th class="white-text" style="background-color: #039B7B;">Pagos recibidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tiposTransacciones as $tipo)
                                        <tr>
                                            <td>{{ $tipo->nombre }}</td>
                                            <td>${{ number_format($tipo->total_abonos + $tipo->total_diferencias + $tipo->venta_directa, 0, '', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td style=" text-align: center;"><strong>Total:</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos")+$tiposTransacciones->sum("total_diferencias")+$tiposTransacciones->sum("venta_directa"),0,'','.') }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Resumen del día --}}
                        <div class="col s12 m3">
                            <h5><strong>Resumen del día</strong></h5>
                            <table class="striped bordered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Total Día</th>
                                        <th class="white-text" style="background-color: #039B7B;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Entradas</td>
                                        
                                        <td>${{ number_format($tiposTransacciones->sum("total_abonos") + $diferenciasAcumuladas, 0, '', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Abonos</td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos"),0,'','.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Total Diferencias</td>
                                        <td>${{ number_format($diferenciasAcumuladas,0,'','.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Consumo</td>
                                        <td>${{ number_format($totalConsumo + $tiposTransacciones->sum("venta_directa"),0,'','.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Servicios Extras</td>
                                        <td>${{ number_format($totalServicios,0,'','.') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Día</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos") + $diferenciasAcumuladas + $totalConsumo + $totalServicios,0,'','.') }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Poro Poro --}}
                        <div class="col s12 m3">
                            <h5><strong>Ventas Poro Poro del día</strong></h5>
                            <table class="striped bordered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Tipo</th>
                                        <th class="white-text" style="background-color: #039B7B;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tiposTransacciones as $tipo)
                                        <tr>
                                            <td>{{ $tipo->nombre }}</td>
                                            <td>${{ number_format($tipo->poro,0,'','.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td style=" text-align: center;"><strong>Total:</strong></td>
                                        <td>${{ number_format($tiposTransacciones->sum('poro'),0,'','.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
@endsection

@section('foot')
    <script>
  // Inicializa los <select> de Materialize 0.100.2
  $(document).ready(function(){
    $('select').material_select();

    // Navegación por mes/año
    $('#mes_anio').on('change', function () {
      var valor = $(this).val(); // "MM-YYYY"
      var partes = valor.split('-');
      var mes  = partes[0];
      var anio = partes[1];

      // Crea y envía un formulario GET a la ruta con ?mes=&anio=
      var form = $('<form>', { method: 'GET', action: "{{ route('backoffice.finanzas.ingresos_percibidos') }}" });
      form.append($('<input>', { type: 'hidden', name: 'mes',  value: mes }));
      form.append($('<input>', { type: 'hidden', name: 'anio', value: anio }));
      $('body').append(form);
      form.submit();
    });
  });
</script>



<script>
$(function(){
  $('.modal').modal();
  $('select').material_select();

  const MES_ACTUAL = "{{ (int)$mes }}-{{ (int)$anio }}";

  function limitar(){
    var vals = $('#meses_comparar').val() || [];
    if (vals.length > 3) {
      alert('Solo puedes comparar hasta 3 meses adicionales.');
      $('#meses_comparar').val(vals.slice(0,3));
      $('#meses_comparar').material_select();
      return false;
    }
    return true;
  }
  $('#meses_comparar').on('change', limitar);

  $('#btn-comparar').on('click', function(e){
    e.preventDefault();
    if (!limitar()) return;

    var adicionales = $('#meses_comparar').val() || [];
    var meses = [MES_ACTUAL].concat(adicionales); // máx 4

    $('#resultadoComparativa').html(
      '<div class="center-align" style="padding:16px 0;">'
      + '<div class="preloader-wrapper small active"><div class="spinner-layer spinner-blue-only">'
      + '<div class="circle-clipper left"><div class="circle"></div></div>'
      + '<div class="gap-patch"><div class="circle"></div></div>'
      + '<div class="circle-clipper right"><div class="circle"></div></div>'
      + '</div></div><p class="grey-text">Calculando comparativa…</p></div>'
    );

    $.ajax({
      url: "{{ route('backoffice.finanzas.comparar') }}",
      method: 'GET',
      data: { 'meses': meses },           // jQuery serializa como meses[]=...
      traditional: false,                 // OK para arrays
      dataType: 'html',
      success: function(html){
        $('#resultadoComparativa').html(html);
      },
      error: function(xhr){
        console.error('Status:', xhr.status, 'Response:', xhr.responseText);
        $('#resultadoComparativa').html(
          '<p class="red-text">No fue posible generar la comparativa. '
          + 'Status '+xhr.status+': '+(xhr.responseText || 'Error').toString().substring(0,300)
          + '</p>'
        );
      }
    });
  });
});
</script>

@endsection