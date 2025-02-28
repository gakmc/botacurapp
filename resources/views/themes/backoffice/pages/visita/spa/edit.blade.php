@extends('themes.backoffice.layouts.admin')

@section('title','Modificar Horarios SPA')

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.show', $cliente->id) }}">Reservas del cliente</a></li>
<li>Crear Reserva</li> --}}
@endsection

@section('content')
<div class="section">
  <p class="caption">Introduce los datos para modificar los Horarios SPA</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
      <div class="row">
          <div class="col s12 m10 offset-m1 ">
              <div class="card-panel">
                  <h4 class="header">Modificar SPA para <strong>{{$reserva->cliente->nombre_cliente}}</strong> -
                      Fecha:<strong>{{$reserva->fecha_visita}}</strong></h4>
                  <div class="row">
                      <form class="col s12" method="post"
                          action="{{route('backoffice.reserva.visitas.spa_update', [$reserva, $visita])}}">


                          {{csrf_field() }}
                          @method('PUT')


                          <div class="row"><br></div>

                          @foreach ($spas as $id=>$spa)
                            
                            @if ($spas->count() >= 2)
                                <h6><strong>Horario Grupo {{$id+1}}</strong></h6><br>
                            @endif
                                <div class="row">

                                    <div class="input-field col s12 m6 ">
                                        <select name="spas[{{ $spa->id }}][horario_sauna]" id="id_producto_entrada_{{ $spa->id }}">
                                            <option value="{{$spa->horario_sauna}}" selected>{{$spa->horario_sauna}}</option>
                                            @foreach($horarios as $horario)
                                            <option value="{{ $horario }}">
                                                {{ $horario }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('id_producto_entrada')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                        <label for="id_producto_entrada_{{ $spa->id }}" class="black-text">Horario SPA</label>
                                    </div>

                                    <div class="input-field col s12 m6">
                                        <input type="text" name="spas[{{ $spa->id }}][observacion]"
                                            id="observacion_{{ $spa->id }}" value="{{ $spa->observacion }}"/>
                                        <label for="observacion_{{$spa->id}}">Observaciones</label>
                                        @error('id_producto_entrada')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                </div>
                        @endforeach

                                  <div class="col s12 m6 l4">
                                    <label for="trago_cortesia" class="black-text">Trago cortesia</label>
                                    <p>
                                        <label>
                                            <input name="trago_cortesia" id="trago_cortesia" type="radio"
                                                class="with-gap" value="Si" @if ($reserva->programa->nombre_programa === 'Botacura Full')
                                                    checked
                                                    @else
                                                    ''
                                                @endif>
                                            <span class="black-text">Si</span>
                                        </label>

                                        <label>
                                            <input name="trago_cortesia" id="trago_cortesia" type="radio"
                                                class="with-gap" value="No" @if($reserva->programa->nombre_programa === "Botacura Full") '' @else checked @endif/>
                                            <span class="black-text">No</span>
                                        </label>
                                    </p>

                                    @error('trago_cortesia')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror

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
    @if(session('success'))
        Swal.fire({
            toast: true,
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
        });
    @endif

    @if(session('error'))
        Swal.fire({
            toast: true,
            icon: 'error',
            title: '{{ session('error') }}',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
        });
    @endif
</script>


@endsection