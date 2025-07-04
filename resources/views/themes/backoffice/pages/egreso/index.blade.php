@extends('themes.backoffice.layouts.admin')

@section('title','Egresos Anuales')

@section('breadcrumbs')
<li>Egresos Anuales</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li>
<li><a href="{{ route('backoffice.proveedor.index') }}" class="grey-text text-darken-2">Proveedores</a></li>
@endsection

@section('content')
<div class="section">
    <div class="card-panel">
      <div class="row center">

        <div class="input-field col s12 m6 offset-m3">
            <select id="anio" name="anio" onchange="cambiarAnio(this.value)">
                @foreach ($añosDisponibles as $a)
                    <option value="{{ $a }}" {{ $a == $anio ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
            <label for="anio">Selecciona Año</label>
        </div>

      </div>

      <div class="row">
        <div class="col s12 m10 offset-m1">

          <h5 class="center">Resumen Egresos - Año {{ $anio }}</h5>

          <table class="responsive-table">
              <thead>
                  <tr>
                      <th>Mes</th>
                      <th>Cantidad de egresos</th>
                      <th>Total Boletas</th>
                      <th>Total Facturas</th>
                      <th>Total mensual</th>
                      {{-- <th>Acción</th> --}}
                  </tr>
              </thead>
              <tbody>
                @php
                  $totalBoletas = 0;
                  $totalFacturas = 0;
                  $totalAnual = 0;
                @endphp
                  @foreach ($egresos as $egreso)
                      @php
                          $nombreMes = \Carbon\Carbon::createFromDate($egreso->anio, $egreso->mes, 1)
                              ->locale('es')->translatedFormat('F Y');
                      @endphp
                      <tr>
                          <td><a href="{{ route('backoffice.egreso.mes', [$egreso->anio, $egreso->mes]) }}">{{ ucfirst($nombreMes) }}</a></td>
                          <td>{{ $egreso->cantidad }}</td>
                          <td>{{ $egreso->cantidad_boletas }}</td>
                          <td>{{ $egreso->cantidad_facturas }}</td>
                          <td>${{ number_format($egreso->total_mes, 0, ',', '.') }}</td>
                          @php
                            $totalBoletas += $egreso->cantidad_boletas;
                            $totalFacturas += $egreso->cantidad_facturas;
                            $totalAnual += $egreso->total_mes;
                          @endphp
                          {{-- <td>
                              <a href="{{ route('backoffice.egreso.mes', [$egreso->anio, $egreso->mes]) }}" class="btn-small" style="background-color: #039B7B">
                                  Ver detalle
                              </a>
                          </td> --}}
                      </tr>
                  @endforeach
                </tbody>
                <tr>
                  <td colspan="3"></td>
                  <td><strong>Total Anual:</strong></td>
                  <td><strong>${{number_format($totalAnual, 0, ',', '.')}}</strong></td>
                </tr>
              </table>

        </div>
      </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    function cambiarAnio(anio) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = "{{ route('backoffice.egreso.index') }}";

        const inputAnio = document.createElement('input');
        inputAnio.name = 'anio';
        inputAnio.value = anio;
        form.appendChild(inputAnio);

        document.body.appendChild(form);
        form.submit();
    }

    $(document).ready(function () {
        $('select').material_select();
    });
</script>

@endsection
