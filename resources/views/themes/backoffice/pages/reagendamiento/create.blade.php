@extends('themes.backoffice.layouts.admin')

@section('title','Reagendar reserva')

@section('head')
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.date.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.time.css') }}">
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $reserva) }}">Reagendamiento para reserva del cliente</a></li>
<li>Crear Reagendamiento</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear un nuevo reserva</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header">Reagendar reserva para <strong>{{$reserva->cliente->nombre_cliente}}</strong></h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.reserva.reagendamientos.store', $reserva)}}">


                        {{csrf_field() }}
 
                            

                            <div class="row">
                                <div class="input-field col s12">
                                <input id="id_reserva" type="hidden" class="form-control" name="id_reserva" value="{{$reserva->id}}" required>

                                <p>Fecha Nueva Visita: </p>
                                
                                  <input id="nueva_fecha" type="date" name="nueva_fecha" class="" value="{{ old('nueva_fecha') }}" placeholder="fecha Visita">
                                    @error('nueva_fecha')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                




                                </div>


                            </div>     



                         


                          <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" type="submit">Guardar
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
<script src="{{ asset('assets/pickadate/lib/picker.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.date.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.time.js') }}"></script>

<script>
  $(document).ready(function () {

    $('#nueva_fecha').pickadate({
      format: 'dd-mm-yyyy',
    })

  });
</script>
@endsection
