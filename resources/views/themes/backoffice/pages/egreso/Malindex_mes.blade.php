@extends('themes.backoffice.layouts.admin')

@section('title','Egresos')

@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos Anuales</a></li>
<li>Egresos {{ ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY')) }}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li>
@endsection

@section('content')
<div class="section">
  <p class="caption"><strong>Egresos mes de {{ ucfirst(\Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM')) }} {{ $anio }}</strong></p>
  <div class="divider"></div>

  <div id="basic-form" class="section">
    {{-- Selector Mes/Año (opcional si ya vienes filtrando por ruta /{anio}/{mes}) --}}
    @if(isset($fechasDisponibles))
    <div class="row">
      <div class="input-field col s12 m6 offset-m3">
        <select id="mes_anio" onchange="cambiarMesAnio(this.value)">
          @foreach($fechasDisponibles as $f)
            @php
              $value = $f->mes . '-' . $f->anio;
              $mesNombre = ucfirst(\Carbon\Carbon::create()->month($f->mes)->locale('es')->isoFormat('MMMM'));
            @endphp
            <option value="{{ $value }}" {{ $mes.'-'.$anio == $value ? 'selected' : '' }}>
              {{ $mesNombre }} {{ $f->anio }}
            </option>
          @endforeach
        </select>
        <label for="mes_anio">Selecciona Mes y Año</label>
      </div>
    </div>
    @endif

    {{-- Totales mes --}}
    <h5 class="center-align">Total Mensual:</h5>
    <div class="row">
      <div class="col s10 m3"><div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
        <span class="white-text"><strong>Neto: </strong>${{ number_format($totalMes['neto'],0,',','.') }}</span>
      </div></div>
      <div class="col s10 m3"><div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
        <span class="white-text"><strong>IVA (19%): </strong>${{ number_format($totalMes['iva'],0,',','.') }}</span>
      </div></div>
      <div class="col s10 m3"><div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
        <span class="white-text"><strong>Impuesto Adicional: </strong>${{ number_format($totalMes['impuesto_incluido'],0,',','.') }}</span>
      </div></div>
      <div class="col s10 m3"><div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
        <span class="white-text"><strong>Total: </strong>${{ number_format($totalMes['total'],0,',','.') }}</span>
      </div></div>
    </div>

    <div class="divider" style="margin: 40px 0;"></div>

    {{-- Semanas --}}
    @forelse($semanas as $clave => $semana)
      <h4 class="blue-text text-darken-3"><strong>{{ $semana['rango'] }}</strong></h4>

      @foreach (['Gastos Fijos','Gastos Variables'] as $tipo)
        <h5 class="grey-text text-darken-2">{{ $tipo }}</h5>

        @php $egresosTipo = $semana[$tipo]; @endphp

        @if(count($egresosTipo) > 0)
        <form action="{{-- route('backoffice.egreso.pago.store') --}}" method="POST">
          @csrf
          <input type="hidden" name="anio" value="{{ $anio }}">
          <input type="hidden" name="mes" value="{{ $mes }}">
          <input type="hidden" name="tipo_gasto" value="{{ $tipo }}">

          <table class="striped responsive-table centered">
            <thead>
              <tr>
                <th>Tipo Doc</th>
                <th>Subcategoría</th>
                <th>Proveedor</th>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Neto</th>
                <th>IVA</th>
                <th>Impuesto</th>
                <th>Total</th>
                <th>Pagar</th>
              </tr>
            </thead>
            <tbody>
              @php $totalTabla = 0; @endphp
              @foreach($egresosTipo as $e)
                @php
                  $esFactura = isset($e->tipo_documento) && strcasecmp($e->tipo_documento->nombre,'Factura')===0;
                  $pagos = $pagosPorEgreso[$e->id]['pagos'] ?? []; // array de pagos previos
                  $montoPagado = $pagosPorEgreso[$e->id]['monto_pagado'] ?? 0;
                  $yaPagadoFijoMes = $bloqueosFijoMes[$e->subcategoria_id] ?? false; // para fijos
                  $pendiente = max(($e->total - $montoPagado),0);
                  $totalTabla += $e->total;
                @endphp
                <tr>
                  <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>
                  <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                  <td>{{ $esFactura ? ($e->proveedor->nombre ?? '-') : '-' }}</td>
                  <td>{{ $esFactura ? ($e->folio ?? '-') : '-' }}</td>
                  <td>{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('D [de] MMM') }}</td>
                  <td>{{ $esFactura ? '$'.number_format($e->neto,0,',','.') : '-' }}</td>
                  <td>{{ $esFactura ? '$'.number_format($e->iva,0,',','.') : '-' }}</td>
                  <td>${{ number_format($e->impuesto_incluido ?? 0,0,',','.') }}</td>
                  <td><strong>${{ number_format($e->total,0,',','.') }}</strong></td>
                  <td class="left-align" style="min-width:220px">
                    @if($tipo==='Gastos Fijos')
                      @if($yaPagadoFijoMes)
                        <span class="tooltipped" data-position="bottom" data-tooltip="Pagado este mes">
                          <i class="material-icons tiny" style="color:#039B7B">monetization_on</i> Pagado
                        </span>
                      @else
                        <div class="row" style="margin-bottom:0">
                          <div class="col s7">
                            <input type="number" min="0" step="1" name="items[{{ $e->id }}][monto]" value="{{ $e->total }}" class="browser-default" style="height:32px;padding:0 6px;">
                          </div>
                          <div class="col s5">
                            <label>
                              <input type="checkbox" class="checkbox-pago" name="items[{{ $e->id }}][check]" data-total="{{ $e->total }}" data-tipo="fijo">
                              <span>Pagar</span>
                            </label>
                          </div>
                        </div>
                      @endif
                    @else
                      <div class="row" style="margin-bottom:0">
                        <div class="col s7">
                          <input type="number" min="0" step="1" name="items[{{ $e->id }}][monto]" value="{{ $pendiente }}" class="browser-default" style="height:32px;padding:0 6px;">
                          <small>Pagado: ${{ number_format($montoPagado,0,',','.') }} / Pend.: ${{ number_format($pendiente,0,',','.') }}</small>
                        </div>
                        <div class="col s5">
                          <label>
                            <input type="checkbox" class="checkbox-pago" name="items[{{ $e->id }}][check]" data-total="{{ $pendiente }}" data-tipo="variable">
                            <span>Pagar</span>
                          </label>
                        </div>
                      </div>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <div class="right-align" style="margin-top:10px">
            <span class="mr-2" id="contador-{{ md5($clave.$tipo) }}">0 seleccionados - $0</span>
            <button type="submit" class="btn waves-effect waves-light">
              Registrar pagos <i class="material-icons right">monetization_on</i>
            </button>
          </div>
        </form>
        @else
          <p class="grey-text">No hay egresos en esta categoría.</p>
        @endif
      @endforeach

      <div class="center-align" style="margin-top: 18px; margin-bottom: 40px;">
        <strong>Total semana:</strong>
        Neto: <strong>${{ number_format($semana['totales']['neto'],0,',','.') }}</strong> &nbsp;
        IVA: <strong>${{ number_format($semana['totales']['iva'],0,',','.') }}</strong> &nbsp;
        Impuesto adicional: <strong>${{ number_format($semana['totales']['impuesto_incluido'],0,',','.') }}</strong> &nbsp;
        Total: <strong>${{ number_format($semana['totales']['total'],0,',','.') }}</strong>
      </div>
    @empty
      <p>No hay registros para este período.</p>
    @endforelse
  </div>
</div>
@endsection

@section('foot')
<script>
  $(function(){
    $('select').material_select({classes:'left-text'});

    $('.checkbox-pago').on('change', function(){
      // Sumar por formulario
      const form = $(this).closest('form');
      let suma = 0, cuenta = 0;
      form.find('.checkbox-pago:checked').each(function(){
        const fila = $(this).closest('tr');
        const input = fila.find('input[type="number"]');
        const val = parseInt(input.val()||0);
        if(!isNaN(val) && val>0){ suma += val; cuenta++; }
      });
      const key = '{{ md5("") }}' + form.find('input[name="tipo_gasto"]').val();
      form.find('[id^="contador-"]').text(cuenta+' seleccionados - $'+suma.toLocaleString('es-CL'));
    });
  });

  function cambiarMesAnio(valor){
    const [mes,anio] = valor.split('-');
    const f = document.createElement('form');
    f.method='GET'; f.action="{{-- route('backoffice.egreso.index_mes', ['anio'=>$anio??$anio,'mes'=>$mes??$mes]) --}}".replace('%7Banio%7D', anio).replace('%7Bmes%7D', mes);
    document.body.appendChild(f); f.submit();
  }
</script>
@endsection
