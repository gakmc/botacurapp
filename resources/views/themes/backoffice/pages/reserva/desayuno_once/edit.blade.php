@extends('themes.backoffice.layouts.admin')

@section('title','Modificar Desayuno/Once')

@section('breadcrumbs')
@endsection

@section('content')
<div class="section">
  <p class="caption">Introduce los datos para Modificar Desayuno/Once</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
      <div class="row">
          <div class="col s12 m10 offset-m1 ">
              <div class="card-panel">
                  <h4 class="header">Modificar Desayuno/Once para <strong>{{$reserva->cliente->nombre_cliente}}</strong> -
                      Fecha:<strong>{{$reserva->fecha_visita}}</strong></h4>
                  <div class="row">
                      <form class="col s12" method="post"
                          action="{{route('backoffice.reserva.desayuno_once_update', $reserva)}}">

                          {{csrf_field() }}
                          @method('PUT')

                          <div class="row"><br></div>
                          <div class="row">
                              <div class="col s12">
                                  <h6><strong>Desayuno u Once</strong></h6>
                                  <p>Seleccione la opción para los {{ $reserva->cantidad_personas }} asistentes:</p>
                                  <label>
                                      <input name="desayuno_once" type="radio" class="with-gap" value="desayuno" required
                                          {{ old('desayuno_once', $tipoActual) === 'desayuno' ? 'checked' : '' }} />
                                      <span class="black-text">Desayuno</span>
                                  </label>
                                  <label style="margin-left: 20px;">
                                      <input name="desayuno_once" type="radio" class="with-gap" value="once" required
                                          {{ old('desayuno_once', $tipoActual) === 'once' ? 'checked' : '' }} />
                                      <span class="black-text">Once</span>
                                  </label>
                                  @error('desayuno_once')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                              </div>
                          </div>

                          <div class="row">
                              <div class="input-field col s12">
                                  <button class="btn waves-effect waves-light right" type="submit">Actualizar
                                      <i class="material-icons right">send</i>
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
<script>
    @if(session('info'))
        Swal.fire({
            icon: 'info',
            title: 'Advertencia',
            text: '{{ session('info') }}',
            showConfirmButton: true,
            confirmButtonText: `Confirmar`,
        });
    @endif

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
            title: 'Alerta',
            text: '{{ session('error') }}',
            showConfirmButton: true,
            confirmButtonText: `Confirmar`,
        });
    @endif
</script>
@endsection
