@extends('themes.backoffice.layouts.admin')

@section('title', 'Abonos de '.$reserva->cliente->nombre_cliente)

@section('head')
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.date.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.time.css') }}">
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $reserva) }}">Reserva del cliente</a></li>
<li>Abonos</li>
@endsection

@section('content')
<div class="section">
  <p class="caption">Historial de abonos extra para la reserva de <strong>{{ $reserva->cliente->nombre_cliente }}</strong></p>
  <div class="divider"></div>

  <div id="basic-form" class="section">
    <div class="row">
      <div class="col s12 m10 offset-m1">

        <div class="card-panel">
          <div class="row" style="margin-bottom: 0">
            <div class="col s12 m4">
              <p>Abono inicial</p>
              <h6><strong>${{ number_format($venta->abono_programa ?? 0, 0, '', '.') }}</strong></h6>
            </div>
            <div class="col s12 m4">
              <p>Total abonado extra</p>
              <h6><strong>${{ number_format($venta->abonosExtra->sum('monto'), 0, '', '.') }}</strong></h6>
            </div>
            <div class="col s12 m4">
              <p>Saldo pendiente</p>
              <h6><strong>${{ number_format($venta->total_pagar, 0, '', '.') }}</strong></h6>
            </div>
          </div>
        </div>

        <div class="card-panel">
          <h5>Historial de abonos extra</h5>
          @if ($venta->abonosExtra->isEmpty())
            <p>Aún no se han registrado abonos extra para esta reserva.</p>
          @else
            <table class="responsive-table highlight">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Monto</th>
                  <th>Tipo transacción</th>
                  <th>Folio</th>
                  <th>Registrado por</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($venta->abonosExtra->sortByDesc('fecha_abono') as $abono)
                  <tr>
                    <td>{{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}</td>
                    <td>${{ number_format($abono->monto, 0, '', '.') }}</td>
                    <td>{{ optional($abono->tipoTransaccion)->nombre ?? 'No registra' }}</td>
                    <td>{{ $abono->folio ?? 'No registra' }}</td>
                    <td>{{ optional($abono->user)->name ?? 'No registra' }}</td>
                    <td>
                      <form method="post" action="{{ route('backoffice.abonos.destroy', $abono) }}" class="form-eliminar-abono" style="display:inline">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                        <a href="#" class="btn-eliminar-abono red-text" data-tooltip="Eliminar abono">
                          <i class="material-icons">delete</i>
                        </a>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @endif
        </div>

        <div class="card-panel">
          <h5>Registrar nuevo abono</h5>
          <div class="row">
          <form class="col s12" method="post" action="{{ route('backoffice.reserva.abonos.store', $reserva) }}">
            {{ csrf_field() }}

            <div class="row">
              <div class="input-field col s12 m4">
                <input id="monto" type="number" min="1" name="monto" value="{{ old('monto') }}" required>
                <label for="monto">Monto del abono</label>
                @error('monto')
                <span class="invalid-feedback" role="alert">
                  <strong style="color:red">{{ $message }}</strong>
                </span>
                @enderror
              </div>

              <div class="input-field col s12 m4">
                <input id="fecha_abono" type="text" name="fecha_abono" class="" value="{{ old('fecha_abono') }}" required>
                <label for="fecha_abono">Fecha del abono</label>
                @error('fecha_abono')
                <span class="invalid-feedback" role="alert">
                  <strong style="color:red">{{ $message }}</strong>
                </span>
                @enderror
              </div>

              <div class="input-field col s12 m4">
                <select name="id_tipo_transaccion" id="id_tipo_transaccion" required>
                  <option value="" disabled selected>-- Seleccione --</option>
                  @foreach ($tipos as $tipo)
                    <option value="{{ $tipo->id }}" {{ old('id_tipo_transaccion') == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                  @endforeach
                </select>
                <label for="id_tipo_transaccion">Tipo Transacción</label>
                @error('id_tipo_transaccion')
                <span class="invalid-feedback" role="alert">
                  <strong style="color:red">{{ $message }}</strong>
                </span>
                @enderror
              </div>
            </div>

            <div class="row">
              <div class="input-field col s12 m6">
                <input id="folio" type="text" name="folio" value="{{ old('folio') }}">
                <label for="folio">Folio (opcional)</label>
                @error('folio')
                <span class="invalid-feedback" role="alert">
                  <strong style="color:red">{{ $message }}</strong>
                </span>
                @enderror
              </div>
            </div>

            <div class="row">
              <div class="input-field col s12">
                <button class="btn waves-effect waves-light right" type="submit">Registrar abono
                  <i class="material-icons right">save</i>
                </button>
              </div>
            </div>
          </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('foot')

<script src="{{ asset('assets/pickadate/lib/picker.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.date.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.time.js') }}"></script>

<script>
    $(document).ready(function () {
      $('select').material_select();
    });

  $(document).ready(function () {

    $('#fecha_abono').pickadate({
      format: 'dd-mm-yyyy',
      formatSubmit: 'yyyy-mm-dd',
      hiddenName: true,
      max: true,
      monthsFull: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
      monthsShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
      weekdaysFull: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
      weekdaysShort: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
      weekdaysLetter: ['D','L','M','M','J','V','S'],
      today: 'Hoy',
      clear: 'Limpiar',
      close: 'Cerrar',
      firstDay: 1,
    })

  });
</script>

<script>
  $(document).ready(function () {
    $('.btn-eliminar-abono').on('click', function (e) {
      e.preventDefault();
      const form = $(this).closest('form');

      Swal.fire({
        title: '¿Eliminar este abono?',
        text: 'El monto se sumará de vuelta al saldo pendiente de la reserva.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
      }).then(function (result) {
        if (result.isConfirmed || result.value) {
          form.submit();
        }
      });
    });
  });
</script>

<script>
  @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: '{{ session('success') }}',
        showConfirmButton: true,
        confirmButtonText: `Confirmar`,
        timer: 5000,
    });
  @endif
  @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Atención',
        text: '{{ session('error') }}',
        showConfirmButton: true,
        confirmButtonText: `Confirmar`
    });
  @endif
</script>
@endsection
