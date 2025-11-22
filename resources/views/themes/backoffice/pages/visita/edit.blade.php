@extends('themes.backoffice.layouts.admin')

@section('title','Modificar Visita')

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.show', $cliente->id) }}">Reservas del cliente</a></li>
<li>Crear Reserva</li> --}}
@endsection

@section('content')
<div class="section">
  <p class="caption">Introduce los datos para Modificar la Visita</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
      <div class="row">
          <div class="col s12 m10 offset-m1 ">
              <div class="card-panel">
                  <h4 class="header">Modificar visita para <strong>{{$reserva->cliente->nombre_cliente}}</strong> -
                      Fecha:<strong>{{$reserva->fecha_visita}}</strong></h4>
                  <div class="row">
                      @php
                      $indexSpa = ceil($reserva->cantidad_personas/5);
                      $indexMasajes = ceil($reserva->cantidad_personas/2);
                      @endphp
                      <form class="col s12" method="post"
                          action="{{route('backoffice.reserva.visitas.update', [$reserva, $visita])}}">


                          {{csrf_field() }}
                          @method('PUT')




                          <div class="input-field col s12 m6 l4" hidden>
                              <input id="id_reserva" type="hidden" class="form-control" name="id_reserva"
                                  value="{{$reserva->id}}" required>
                          </div>

                          @if ($reserva->cantidad_personas <= 2)
                          <div class="row">
                              <div class="input-field col s12 m6 l4">
                                  <select name="horario_sauna" id="horario_sauna">
                                      <option value="{{$visita->horario_sauna ?? old('horario_sauna')}}" selected>{{$visita->horario_sauna}}</option>
                                      @foreach($horarios as $horario)
                                      <option value="{{ $horario }}" {{ old('horario_sauna') === $horario ? 'selected'
                                          : '' }}>
                                          {{ $horario }}
                                      </option>
                                      @endforeach
                                  </select>
                                  @error('horario_sauna')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                                  <label for="horario_sauna">Horario SPA</label>
                              </div>

                              <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>

                                  <select id="horario_masaje" name="horario_masaje" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>

                                      <option value="{{$reserva->masajes->first()->horario_masaje}}" selected>{{$reserva->masajes->first()->horario_masaje}}</option>
                                      {{-- @foreach($horasMasaje as $horario)
                                      <option value="{{ $horario }}" {{ old('horario_sauna')==$horario ? 'selected'
                                          : '' }}>
                                          {{ $horario }}
                                      </option>
                                      @endforeach --}}

                                  </select>
                                  <label for="horario_masaje">Horario Masaje</label>
                                  @error('horario_masaje')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror

                              </div>

                              <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>

                                  <select id="tipo_masaje" name="tipo_masaje" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>

                                    
                                      <option value="Relajación" {{ $visita->tipo_masaje === 'Relajación' ? 'selected' : ''
                                          }}>
                                          Relajación
                                      </option>
                                      <option value="Descontracturante" {{ $visita->tipo_masaje === 'Descontracturante'
                                          ? 'selected' : '' }}>
                                          Descontracturante
                                      </option>



                                  </select>
                                  <label for="tipo_masaje">Tipo Masaje</label>
                                  @error('tipo_masaje')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror

                              </div>
                          </div>
                              
                          @elseif ($reserva->cantidad_personas <= 5)
                          
                          <div class="row">
                              <h6><strong>SPA</strong></h6>
                              <div class="input-field col s12 m6 l4">

                                  <select name="horario_sauna" id="horario_sauna">
                                      <option value="{{$visita->horario_sauna}}" selected>{{$visita->horario_sauna}}</option>
                                      @foreach($horarios as $horario)
                                      <option value="{{ $horario }}" {{ old('horario_sauna')==$horario ? 'selected'
                                          : '' }}>
                                          {{ $horario }}
                                      </option>
                                      @endforeach
                                  </select>
                                  @error('horario_sauna')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                                  <label for="horario_sauna">Horario SPA</label>
                              </div>

              </div>
              <div class="row">
      
                              <h6><strong>Masajes</strong></h6>
                              @if(!in_array('Masaje', $servicios) & !$masajesExtra) <br> <h6>Esta Visita no posee masajes</h6> @endif
                              @for ($i = 1; $i <= $indexMasajes; $i++)

                              {{-- {{dd($masajes[$i])}} --}}
                        <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) & !$masajesExtra) style="display: none;" @endif>
                              <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>
                                
                                  <option value="{{ isset($masajes[$i]) ? $masajes[$i]->horario_masaje : '' }}" selected>{{ isset($masajes[$i]) ? $masajes[$i]->horario_masaje : '-- Seleccione --' }}</option>


                              </select>
                              <label for="horario_masaje_{{$i}}">Horario Masaje</label>
                              @error('horario_masaje_{{$i}}')
                              <span class="invalid-feedback" role="alert">
                                  <strong style="color:red">{{ $message }}</strong>
                              </span>
                              @enderror

                          </div>

                          <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>
{{-- {{dd($masajes[$i])}} --}}
                              <select id="tipo_masaje_{{$i}}" name="masajes[{{$i}}][tipo_masaje]" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>


                                  <option value="Relajación" {{ isset($masajes[$i]->tipo_masaje) ==='Relajación' ? 'selected' : ''
                                      }}>
                                      Relajación
                                  </option>
                                  <option value="Descontracturante" {{ isset($masajes[$i]->tipo_masaje) ==='Descontracturante'
                                      ? 'selected' : '' }}>
                                      Descontracturante
                                  </option>



                              </select>
                              <label for="tipo_masaje">Tipo Masaje</label>
                              @error('tipo_masaje')
                              <span class="invalid-feedback" role="alert">
                                  <strong style="color:red">{{ $message }}</strong>
                              </span>
                              @enderror

                          </div>

                          <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>
                          <select name="masajes[{{$i}}][id_lugar_masaje]" id="id_lugar_masaje_{{$i}}" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>
                              @foreach ($lugares as $lugar)
                              <option value="{{$lugar->id}}" {{ isset($masajes[$i]->id_lugar_masaje) === $lugar->id ?
                                  'selected' : '' }}>{{$lugar->nombre}}</option>
                              @endforeach
                          </select>
                          @error('id_lugar_masaje_{{$i}}')
                          <span class="invalid-feedback" role="alert">
                              <strong style="color:red">{{ $message }}</strong>
                          </span>
                          @enderror
                          <label for="id_lugar_masaje_{{$i}}">Lugar Masaje</label>
                      </div>


                              @endfor
                          </div>

                          @else
                              <div class="row">
                                  <h6><strong>SPA</strong></h6>
                                  @for ($i = 1; $i <= $indexSpa; $i++)
                                  <div class="input-field col s12 m6 l4">
                                      <h6>Grupo {{$i}}</h6>
                                      <select id="horario_sauna_{{$i}}" name="spas[{{$i}}][horario_sauna]">
                                          <option value="{{isset($visitas[$i-1]) ? $visitas[$i-1]->horario_sauna : '' }}" selected>{{ isset($visitas[$i-1]) ? $visitas[$i-1]->horario_sauna : '---' }}</option>
                                          @foreach($horarios as $horario)
                                          <option value="{{ $horario }}" {{ old("spas.{$i}.horario_sauna")== $horario ? 'selected'
                                              : '' }}>
                                              {{ $horario }}
                                          </option>
                                          @endforeach
                                      </select>

                                      @error('horario_sauna_{{$i}}')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                      <label for="horario_sauna_{{$i}}">Horario SPA</label>
                                  </div>
                                  @endfor

      </div>
      <div class="row">
                                  <h6><strong>Masajes</strong></h6>
                                  @if(!in_array('Masaje', $servicios) & !$masajesExtra) <br> <h6>Esta Visita no posee masajes</h6> @endif
                                  @for ($i=1; $i<=$indexMasajes; $i++)
                                  <h6>Par {{$i}}</h6>
                                  <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>


                                  <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]" @if (!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif >

                                      <option value="{{isset($masajes[$i]) ? $masajes[$i]->horario_masaje : '' }}" selected>{{ isset($masajes[$i]) ? $masajes[$i]->horario_masaje : '-- Seleccione --' }}</option>

                                  </select>
                                  <label for="horario_masaje_{{$i}}">Horario Masaje</label>
                                  @error('horario_masaje_{{$i}}')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror

                              </div>

                              <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>

                                  <select id="tipo_masaje_{{$i}}" name="masajes[{{$i}}][tipo_masaje]" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>

                                        <option value="Relajación" {{ isset($masajes[$i]->tipo_masaje) ==='Relajación' ? 'selected' : ''
                                        }}>
                                        Relajación
                                        </option>
                                        <option value="Descontracturante" {{ isset($masajes[$i]->tipo_masaje )==='Descontracturante'
                                        ? 'selected' : '' }}>
                                        Descontracturante
                                        </option>



                                  </select>
                                  <label for="tipo_masaje_{{$i}}">Tipo Masaje</label>
                                  @error('tipo_masaje_{{$i}}')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror

                              </div>

                              <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>
                              <select name="masajes[{{$i}}][id_lugar_masaje]" id="id_lugar_masaje_{{$i}}" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>
                                  @foreach ($lugares as $lugar)
                                  <option value="{{$lugar->id}}" {{ isset($masajes[$i-1]->id_lugar_masaje) === $lugar->id ?
                                  'selected' : '' }}>{{$lugar->nombre}}</option>
                                  @endforeach
                              </select>
                              @error('id_lugar_masaje_{{$i}}')
                              <span class="invalid-feedback" role="alert">
                                  <strong style="color:red">{{ $message }}</strong>
                              </span>
                              @enderror
                              <label for="id_lugar_masaje_{{$i}}">Lugar Masaje</label>
                          </div>

                                  @endfor
                              </div>
                          @endif



                          <div class="row">
                              <div class="input-field col s12 m6 l4">

                                  <label for="observacion">Observaciones - "Decoraciones"</label>
                                  <input id="observacion" type="text" name="observacion" class=""
                                      value="{{ $visita->observacion ?? old('observacion') }}">
                                  @error('observacion')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror

                              </div>


                              <div class="input-field col s12 m6 l4">
                                  <select name="id_ubicacion" id="id_ubicacion">
                                      <option value="{{$visita->id_ubicacion}}" selected>{{$visita->ubicacion->nombre}}</option>
                                      @foreach ($ubicaciones as $ubicacion)
                                      <option value="{{$ubicacion->id}}" {{ old('id_ubicacion') === $ubicacion->nombre ?
                                          'selected' : '' }}>{{$ubicacion->nombre}}</option>
                                      @endforeach
                                  </select>
                                  @error('id_ubicacion')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                                  <label for="id_ubicacion">Ubicación</label>
                              </div>

                              @if ($reserva->cantidad_personas <= 2)
                                @php $masaje = $reserva->masajes->first(); @endphp
                                  
                              
                              <div class="input-field col s12 m6 l4" @if(!in_array('Masaje', $servicios) && !$masajesExtra) style="display: none;" @endif>
                                  <select name="id_lugar_masaje" id="id_lugar_masaje" @if(!in_array('Masaje', $servicios) && !$masajesExtra) disabled hidden @endif>
                                      @foreach ($lugares as $lugar)
                                      <option value="{{$lugar->id}}" {{ optional($masaje)->id_lugar_masaje === $lugar->id ?
                                          'selected' : '' }}>{{$lugar->nombre}}</option>
                                      @endforeach
                                  </select>
                                  @error('id_lugar_masaje')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                                  <label for="id_lugar_masaje">Lugar Masaje</label>
                              </div>
                              @endif

                          </div>



                          <div class="row">

                              <div class="col s12 m6 l4">
                                  <label for="trago_cortesia">Trago cortesia</label>
                                  <p>
                                      <label>
                                          <input name="trago_cortesia" id="trago_cortesia" type="radio"
                                              class="with-gap" value="Si" {{$visita->trago_cortesia === "Si" ? "checked" : ''}}/>
                                          <span class="black-text">Si</span>
                                      </label>

                                      <label>
                                          <input name="trago_cortesia" id="trago_cortesia" type="radio"
                                              class="with-gap" value="No" {{$visita->trago_cortesia === "No" ? "checked" : ''}} />
                                          <span class="black-text">No</span>
                                      </label>
                                  </p>

                                  @error('trago_cortesia')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror

                              </div>
                          </div>





                          <div class="row"><br></div>
                          @if (!in_array('Almuerzo', $servicios) && !$almuerzosExtra)
                          <h6><strong> No registra almuerzos como servicios ni Extras</strong></h6>
                          @else
                          <div class="row">
                              <h6><strong> Menús por asistente</strong></h6>

                              @for ($i = 1; $i <= $reserva->cantidad_personas; $i++)

                              <input type="number" name="menus[{{$i}}][id]" id="id_menu_{{$i}}" value="{{$menus[$i-1]->id}}" hidden>

                                  <div class="input-field col s12 m6 l3">
                                      <select name="menus[{{ $i }}][id_producto_entrada]"
                                          id="id_producto_entrada_{{ $i }}">
                                          <option value="" disabled selected> -- Seleccione --</option>
                                          @foreach ($entradas as $entrada)
                                          <option value="{{$entrada->id}}" {{ $menus[$i-1]->id_producto_entrada === $entrada->id ? 'selected'
                                          : '' }}>{{$entrada->nombre}}</option>
                                          @endforeach
                                      </select>
                                      @error('id_producto_entrada')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                      <label for="id_producto_entrada_{{ $i }}">Entrada</label>
                                  </div>



                                  <div class="input-field col s12 m6 l2">
                                      <select name="menus[{{$i}}][id_producto_fondo]" id="id_producto_fondo_{{$i}}">
                                          <option value="" disabled selected> -- Seleccione --</option>
                                          @foreach ($fondos as $fondo)
                                          <option value="{{$fondo->id}}" {{ $menus[$i-1]->id_producto_fondo === $fondo->id ? 'selected'
                                          : '' }}>{{$fondo->nombre}}</option>
                                          @endforeach
                                      </select>
                                      @error('id_producto_fondo_{{$i}}')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                      <label for="id_producto_fondo_{{ $i }}">Fondo</label>
                                  </div>


                                  <div class="input-field col s12 m6 l2">
                                      <select name="menus[{{$i}}][id_producto_acompanamiento]"
                                          id="id_producto_acompanamiento_{{$i}}">
                                          <option value="" disabled selected> -- Seleccione --</option>
                                          <option value="" {{ $menus[$i-1]->id_producto_acompanamiento === null ? 'selected'
                                          : '' }}>Sin Acompañamiento</option>
                                          @foreach ($acompañamientos as $acompañamiento)
                                          <option value="{{$acompañamiento->id}}" {{ $menus[$i-1]->id_producto_acompanamiento === $acompañamiento->id ? 'selected'
                                          : '' }}>{{$acompañamiento->nombre}}</option>
                                          @endforeach
                                      </select>
                                      @error('id_producto_acompanamiento_{{$i}}')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                      <label for="id_producto_acompanamiento_{{ $i }}">Acompañamiento</label>
                                  </div>

                                  <div class="input-field col s12 m6 l2">

                                      <input id="alergias_{{$i}}" type="text" name="menus[{{ $i }}][alergias]"
                                          class="" value="{{ $menus[$i-1]->alergias }}">
                                      <label for="alergias_{{$i}}">Alérgias</label>
                                      @error('alergias_{{$i}}')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                  </div>


                                  <div class="input-field col s12 m6 l2">
                                      <input type="text" name="menus[{{ $i }}][observacion]"
                                          id="observacion_{{ $i }}" value="{{ $menus[$i-1]->observacion }}"/>
                                      <label for="observacion_{{$i}}">Observaciones</label>
                                      @error('id_producto_entrada')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                  </div>

                                  @endfor

                                  @endif
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


{{-- Carga horario masajes (2 o -) en vista --}}
<script>
  $(document).ready(function () {
        // Cargar horarios desde el backend
        const horariosPorLugar = @json($horasMasaje);
        console.log(horariosPorLugar);
        

        // Inicializa Materialize para todos los selectores
        $('select').material_select();


        // Función para cargar horarios en horario_masaje según el lugar seleccionado
        function cargarHorariosUnico(lugarId) {
            const $horarioSelect = $('#horario_masaje');


            if (horariosPorLugar[lugarId]) {
                horariosPorLugar[lugarId].forEach(function (horario) {
                    $horarioSelect.append(new Option(horario, horario));
                });

                // Reinicializa Materialize para el selector
                $horarioSelect.material_select();
            }
        }

        @php
            $lugarDatabase = optional($reserva->masajes->first())->id_lugar_masaje;
        @endphp

        var lugarDatabase = @json($lugarDatabase);

        // Detectar cambios en el lugar de masaje
        $('#id_lugar_masaje').on('change', function () {
            const $horarioSelect = $('#horario_masaje');
            const lugarId = $(this).val(); // ID del lugar seleccionado

            if (lugarId == lugarDatabase) {
                
                $horarioSelect.empty().append('<option value="{{$visita->horario_masaje}}" selected>{{$visita->horario_masaje}}</option>');
            }else{

                $horarioSelect.empty().append('<option value="" disabled selected>-- Seleccione --</option>');
            }
            cargarHorariosUnico(lugarId); // Actualizar los horarios en horario_masaje
        });

        // Carga inicial: verifica si hay un lugar preseleccionado
        const lugarInicial = $('#id_lugar_masaje').val();
        if (lugarInicial) {
            cargarHorariosUnico(lugarInicial);
        }
    });

</script>

{{-- Carga horario masajes (5 o +) en vista --}}
<script>

    $(document).ready(function () {
        // Cargar horarios desde el backend
        const horariosPorLugar = @json($horasMasaje);

        // Inicializa todos los selectores de Materialize
        $('select').material_select();

        // Función para cargar horarios en el select de horario masaje
        function cargarHorariosInicial(lugarId, index) {
            const $horarioSelect = $(`#horario_masaje_${index}`);
            //$horarioSelect.empty().append('<option value="" disabled selected>-- Seleccione --</option>');

            if (horariosPorLugar[lugarId]) {
                horariosPorLugar[lugarId].forEach(function (horario) {
                    $horarioSelect.append(new Option(horario, horario));
                });

                // Reinicializa Materialize para el selector
                $horarioSelect.material_select();
            }
        }

        // Detectar cambios en el lugar de masaje
        $('[id^="id_lugar_masaje_"]').on('change', function () {
            const lugarId = $(this).val(); // ID del lugar seleccionado
            const index = $(this).attr('id').split('_').pop(); // Índice del selector

            // Cargar los horarios según el lugar seleccionado
            cargarHorariosInicial(lugarId, index);
        });

        // Carga inicial: busca todos los selects que tienen lugar de masaje ya seleccionado
        $('[id^="id_lugar_masaje_"]').each(function () {
            const lugarId = $(this).val(); // ID del lugar seleccionado
            const index = $(this).attr('id').split('_').pop(); // Índice del selector

            // Carga los horarios iniciales para el lugar ya seleccionado
            if (lugarId) {
                cargarHorariosInicial(lugarId, index);
            }
        });
    });

      


</script>
@endsection