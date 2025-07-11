@extends('themes.backoffice.layouts.admin')

@section('title', 'Egresos')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos Anuales</a></li>
<li>Egresos {{ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li>
@endsection

@section('content')
<div class="section">
    <p class="caption">Listado de Egresos <strong>{{ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">


<h5 class="center-align">Total Mensual:</h5>

                <div class="row">
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>Neto: </strong>${{ number_format($totalMes['neto'], 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>IVA (19%): </strong>${{ number_format($totalMes['iva'], 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>Impuesto Adicional: </strong>${{ number_format($totalMes['impuesto_incluido'], 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel gradient-45deg-light-blue-cyan gradient-shadow center">
                      <span class="white-text"><strong>Total: </strong>${{ number_format($totalMes["total"], 0, ',', '.') }}</span>
                    </div>
                  </div>


                </div>

      <div class="divider" style="margin: 40px 0;"></div>
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    {{-- <div class="row">




                      <table class="striped responsive-table">
                        <thead>
                          <tr>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Subcategoría</th>
                            <th>Proveedor</th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Neto</th>
                            <th>IVA</th>
                            <th>Total</th>
                            <th>Acciones</th>
                          </tr>
                        </thead>
                        <tbody>
                          @if ($egresos->isNotEmpty())
                          @foreach ($egresos as $e)
                            <tr>
                              <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>
                              <td>{{ $e->categoria->nombre ?? '-' }}</td>
                              <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                              <td>{{ $e->proveedor->nombre ?? '-' }}</td>
                              <td>{{ $e->folio ?? '-' }}</td>
                              <td>{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</td>
                              <td>${{ number_format($e->neto, 0, ',', '.') }}</td>
                              <td>${{ number_format($e->iva, 0, ',', '.') }}</td>
                              <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
                              <td>
                                
                                <a href="{{ route('backoffice.egreso.edit', $e->id) }}" class="btn-floating btn-small purple">
                                  <i class='material-icons'>edit</i>
                                </a>
                                <form id="form-eliminar-{{$e->id}}" action="{{ route('backoffice.egreso.destroy', $e->id) }}" method="POST" style="display:inline;">
                                  @csrf 
                                  @method('DELETE')
                                  <button type="button" class="btn-floating btn-small red" onclick="confirmarEliminacion({{$e->id}})"><i class='material-icons'>delete</i></button>
                                </form>

                              </td>
                            </tr>
                          @endforeach

                          @else
                            <tr>
                              <td colspan="2"></td>
                              <td><h5>No se registran egresos</h5></td>
                            </tr>
                          @endif
                        </tbody>
                      </table>


                    </div> --}}


                    <div class="row">
                      @php
                        $totalMesNeto = 0;
                        $totalMesIva = 0;
                        $totalMesImpuesto = 0;
                        $totalMesTotal = 0;
                      @endphp

                      @foreach ($semanas as $rango => $semana)
                        <h4 class="blue-text text-darken-3"><strong>{{ $semana['rango'] }}</strong></h4>

                        @php
                          $netoSemana = 0;
                          $ivaSemana = 0;
                          $impuestoSemana = 0;
                          $totalSemana = 0;
                        @endphp

                        @foreach (['Gastos Fijos', 'Gastos Variables'] as $tipo)
                          <h5 class="grey-text text-darken-2">{{ $tipo }}</h5>

                          @php $egresos = $semana[$tipo]; @endphp

                          @if(count($egresos) > 0)
                          <table class="striped responsive-table">
                            <thead>
                              <tr>
                                <th>Tipo</th>
                                <th>Subcategoría</th>
                                <th>Proveedor</th>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Neto</th>
                                <th>IVA</th>
                                <th>Impuesto adicional</th>
                                <th>Total</th>
                                <th>Acciones</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach ($egresos as $e)
                              @php
                                $netoSemana += $e->neto;
                                $ivaSemana += $e->iva;
                                $impuestoSemana += $e->impuesto_incluido;
                                $totalSemana += $e->total;
                              @endphp
                              <tr>
                                <td>{{ $e->tipo_documento->nombre ?? '-' }}</td>
                                <td>{{ $e->subcategoria->nombre ?? '-' }}</td>
                                <td>{{ $e->proveedor->nombre ?? '-' }}</td>
                                <td>{{ $e->folio ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($e->fecha)->locale('es')->isoFormat('D [de] MMMM') }}</td>
                                <td>${{ number_format($e->neto, 0, ',', '.') }}</td>
                                <td>${{ number_format($e->iva, 0, ',', '.') }}</td>
                                <td>${{ number_format($e->impuesto_incluido, 0, ',', '.') }}</td>
                                <td><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
                                <td>
                                  <a href="{{ route('backoffice.egreso.edit', $e->id) }}" class="btn-floating btn-small purple">
                                    <i class='material-icons'>edit</i>
                                  </a>
                                  <form id="form-eliminar-{{$e->id}}" action="{{ route('backoffice.egreso.destroy', $e->id) }}" method="POST" style="display:inline;">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="button" class="btn-floating btn-small red" onclick="confirmarEliminacion({{$e->id}})">
                                      <i class='material-icons'>delete</i>
                                    </button>
                                  </form>
                                </td>
                              </tr>
                              @endforeach
                            </tbody>
                          </table>
                          @if($loop->last)
                            <div class="center-align" style="margin-top: 10px;">
                              <strong>Total semana:</strong><br>
                              Neto: <strong>${{ number_format($semana['totales']['neto'], 0, ',', '.') }}</strong> &nbsp;
                              IVA: <strong>${{ number_format($semana['totales']['iva'], 0, ',', '.') }}</strong> &nbsp;
                              Impuesto adicional: <strong>${{ number_format($semana['totales']['impuesto_incluido'], 0, ',', '.') }}</strong> &nbsp;
                              Total: <strong>${{ number_format($semana['totales']['total'], 0, ',', '.') }}</strong>
                            </div>
                          @endif
                          @else
                            <p class="grey-text">No hay egresos en esta categoría.</p>
                          @endif
                        @endforeach

                        {{-- Totales de la semana --}}
                        {{-- @php
                          $totalMesNeto += $netoSemana;
                          $totalMesIva += $ivaSemana;
                          $totalMesTotal += $totalSemana;
                        @endphp

                        <div class="center-align" style="margin-top: 10px; margin-bottom: 40px;">
                          <h6><strong>Total semana:</strong></h6>
                          Neto: ${{ number_format($netoSemana, 0, ',', '.') }} &nbsp;
                          IVA: ${{ number_format($ivaSemana, 0, ',', '.') }} &nbsp;
                          <strong>Total: ${{ number_format($totalSemana, 0, ',', '.') }}</strong>
                        </div> --}}
                      @endforeach


                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')
<script>
      $(document).ready(function () {
        @if(session('info'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

        @if(session('success'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif
    });
</script>

<script>
    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar este egreso?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-eliminar-' + id).submit();
            }
        });
    }
</script>
@endsection