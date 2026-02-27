@extends('themes.backoffice.layouts.admin')

@section('title','Planificar Visita')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $reserva) }}">Reagendamiento para reserva del cliente</a></li>
<li>Planificar Visita</li>
@endsection



@section('content')
<div class="section">
    <p class="caption">Introduce los datos para planificar una Visita</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m10 offset-m1 ">
                <div class="card-panel">
                    <h4 class="header">Planificar visita para <strong>{{$reserva->cliente->nombre_cliente}}</strong> -
                        Fecha:<strong>{{$reserva->fecha_visita}}</strong></h4>
                    <div class="row">
                        {{-- @php
                        $indexSpa = ceil($reserva->cantidad_personas/5);
                        if (!in_array('Masaje', $servicios)) {
                            $indexMasajes = ceil($cantidadMasajesExtra/2);
                        }else {
                            $indexMasajes = ceil($reserva->cantidad_personas/2);
                        }
                        @endphp --}}

                        {{-- @php
                        $hayMasajes = !is_null($modoMasaje);
                        $indexMasajes = (int) $cantidadSlotsMasaje; // slots ya vienen calculados
                        $indexSpa = (int) ceil($reserva->cantidad_personas / 5);
                        @endphp --}}

                        @php
                            $hayMasajes = !is_null($modoMasaje);

                            // Personas/masajes reales según modo
                            if ($modoMasaje === 'extra') {
                                $personasMasajeView = (int) $cantidadMasajesExtra; // 1 masaje = 1 persona lógica
                            } elseif ($modoMasaje === 'programa') {
                                $personasMasajeView = (int) $reserva->cantidad_personas; // programa: masaje por persona (tu regla)
                            } else {
                                $personasMasajeView = 0;
                            }

                            // Slots: ya vienen calculados desde el controller (fuente de verdad)
                            $slotsMasaje = (int) $cantidadSlotsMasaje;
                        @endphp


                        <form class="col s12" method="post"
                            action="{{route('backoffice.reserva.visitas.store', $reserva)}}">

                            {{csrf_field() }}


                            <div class="input-field col s12 m6 l4" hidden>
                                <input id="id_reserva" type="hidden" class="form-control" name="id_reserva"
                                    value="{{$reserva->id}}" required>
                            </div>

                            @if ($reserva->cantidad_personas <= 2)
                                    <div class="row">

                                        <div class="input-field col s12 m6 l4">
                                            <select name="horario_sauna" id="horario_sauna">
                                                <option value="" selected disabled>-- Seleccione --</option>
                                                @foreach($horarios as $horario)
                                                    <option value="{{ $horario }}" {{ old('horario_sauna')==$horario ? 'selected' : '' }}>{{ $horario }}</option>
                                                @endforeach
                                            </select>
                                            <label for="horario_sauna">Horario SPA</label>
                                        </div>

                                        @if($hayMasajes)
                                            <div class="col s12"><h6><strong>Masajes</strong></h6></div>

                                            @for($i=1; $i <= $slotsMasaje; $i++)

                                                {{-- HORARIO --}}
                                                <div class="input-field col s12 m6 l2">
                                                    <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]">
                                                        <option value="" selected disabled>-- Seleccione --</option>
                                                    </select>
                                                    <label for="horario_masaje_{{$i}}">Horario Masaje</label>
                                                </div>

                                                {{-- EXTRA: pide categoría/tipo/precio por cada masaje --}}
                                                @if($modoMasaje === 'extra')

                                                    <div class="input-field col s12 m6 l2">
                                                        <select id="categoria_masaje_{{$i}}" name="masajes[{{$i}}][categoria_slug]">
                                                            <option value="" disabled selected>-- Categoría --</option>
                                                            @foreach($catalogoMasajes as $cat)
                                                                <option value="{{ $cat['slug'] }}">{{ $cat['nombre'] }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label for="categoria_masaje_{{$i}}">Categoría</label>
                                                    </div>

                                                    <div class="input-field col s12 m6 l2">
                                                        <select id="tipo_masaje_{{$i}}" name="masajes[{{$i}}][tipo_slug]" disabled>
                                                            <option value="" disabled selected>-- Tipo --</option>
                                                        </select>
                                                        <label for="tipo_masaje_{{$i}}">Tipo</label>
                                                    </div>

                                                    <div class="input-field col s12 m6 l2">
                                                        <select id="precio_masaje_{{$i}}" name="masajes[{{$i}}][precio_id]" disabled>
                                                            <option value="" disabled selected>-- Duración / Precio --</option>
                                                        </select>
                                                        <label for="precio_masaje_{{$i}}">Duración</label>
                                                        <small id="info_precio_masaje_{{$i}}" class="grey-text"></small>
                                                    </div>

                                                @else
                                                    {{-- INCLUIDO: slot representa hasta 2 masajes en backend --}}
                                                    <input type="hidden" name="masajes[{{$i}}][tipo_masaje]" value="Relajación">
                                                    <input type="hidden" name="masajes[{{$i}}][tiempo_extra]" value="0">
                                                @endif

                                                {{-- LUGAR --}}
                                                <div class="input-field col s12 m6 l2">
                                                    <select name="masajes[{{$i}}][id_lugar_masaje]" id="id_lugar_masaje_{{$i}}">
                                                        @foreach($lugares as $lugar)
                                                            <option value="{{ $lugar->id }}">{{ $lugar->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label for="id_lugar_masaje_{{$i}}">Lugar Masaje</label>
                                                </div>

                                                <div class="input-field col s12"></div>
                                            @endfor
                                        @endif

                                    </div>
                                
                            @elseif ($reserva->cantidad_personas <= 5)
                            
                                <div class="row">
                                    <h6><strong>SPA</strong></h6>
                                    <div class="input-field col s12 m6 l4">
                                        
                                        <select name="horario_sauna" id="horario_sauna">
                                            <option value="" selected disabled="">-- Seleccione --</option>
                                            @foreach($horarios as $horario)
                                            <option value="{{ $horario }}" {{ old('horario_sauna')==$horario ? 'selected'
                                                : '' }}>{{ $horario }}</option>
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

                                    @if($hayMasajes)
                                    <h6><strong>Masajes</strong></h6>

                                    @for($i=1; $i <= $slotsMasaje; $i++)

                                        {{-- HORARIO (siempre) --}}
                                        <div class="input-field col s12 m6 l2">
                                        <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]">
                                            <option value="" selected disabled>-- Seleccione --</option>
                                        </select>
                                        <label for="horario_masaje_{{$i}}">Horario Masaje</label>
                                        </div>

                                        
                                        @if($modoMasaje === 'extra')
                                        {{-- CATEGORÍA --}}
                                        <div class="input-field col s12 m6 l2">
                                            <select id="categoria_masaje_{{$i}}" name="masajes[{{$i}}][categoria_slug]">
                                            <option value="" disabled selected>-- Categoría --</option>
                                            @foreach($catalogoMasajes as $cat)
                                                <option value="{{ $cat['slug'] }}">{{ $cat['nombre'] }}</option>
                                            @endforeach
                                            </select>
                                            <label for="categoria_masaje_{{$i}}">Categoría</label>
                                        </div>

                                        {{-- TIPO --}}
                                        <div class="input-field col s12 m6 l2">
                                            <select id="tipo_masaje_{{$i}}" name="masajes[{{$i}}][tipo_slug]" disabled>
                                            <option value="" disabled selected>-- Tipo --</option>
                                            </select>
                                            <label for="tipo_masaje_{{$i}}">Tipo</label>
                                        </div>

                                        {{-- DURACIÓN / PRECIO --}}
                                        <div class="input-field col s12 m6 l2">
                                            <select id="precio_masaje_{{$i}}" name="masajes[{{$i}}][precio_id]" disabled>
                                            <option value="" disabled selected>-- Duración / Precio --</option>
                                            </select>
                                            <label for="precio_masaje_{{$i}}">Duración</label>
                                            <small id="info_precio_masaje_{{$i}}" class="grey-text"></small>
                                        </div>
                                        @else
                                        {{-- PROGRAMA --}}
                                        <input type="hidden" name="masajes[{{$i}}][tipo_masaje]" value="Relajación">
                                        <input type="hidden" name="masajes[{{$i}}][tiempo_extra]" value="0">
                                        @endif

                                        {{-- LUGAR (siempre) --}}
                                        <div class="input-field col s12 m6 l2">
                                        <select name="masajes[{{$i}}][id_lugar_masaje]" id="id_lugar_masaje_{{$i}}">
                                            @foreach($lugares as $lugar)
                                            <option value="{{$lugar->id}}">{{$lugar->nombre}}</option>
                                            @endforeach
                                        </select>
                                        <label for="id_lugar_masaje_{{$i}}">Lugar Masaje</label>
                                        </div>

                                        <div class="input-field col s12"></div>
                                    @endfor
                                    @endif

                                </div>

                            
                            @else
                                <div class="row">
                                    <h6><strong>SPA</strong></h6>
                                    @for ($i = 1; $i <= $indexSpa; $i++)
                                    <div class="input-field col s12 m6 l4">
                                        <h6>Grupo {{$i}}</h6>
                                        <select id="horario_sauna_{{$i}}" name="spas[{{$i}}][horario_sauna]">
                                            <option value="" selected disabled="">-- Seleccione --</option>
                                            @foreach($horarios as $horario)
                                            <option value="{{ $horario }}" {{ old("spas.{$i}.horario_sauna")== $horario ? 'selected'
                                                : '' }}>{{ $horario }}</option>
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

                                    @if($hayMasajes)
                                    <h6><strong>Masajes</strong></h6>

                                    @for($i=1; $i <= $slotsMasaje; $i++)

                                        {{-- HORARIO (siempre) --}}
                                        <div class="input-field col s12 m6 l2">
                                        <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]">
                                            <option value="" selected disabled>-- Seleccione --</option>
                                        </select>
                                        <label for="horario_masaje_{{$i}}">Horario Masaje</label>
                                        </div>

                                        {{-- LUGAR (siempre) --}}
                                        <div class="input-field col s12 m6 l2">
                                        <select name="masajes[{{$i}}][id_lugar_masaje]" id="id_lugar_masaje_{{$i}}">
                                            @foreach($lugares as $lugar)
                                            <option value="{{$lugar->id}}">{{$lugar->nombre}}</option>
                                            @endforeach
                                        </select>
                                        <label for="id_lugar_masaje_{{$i}}">Lugar Masaje</label>
                                        </div>

                                        @if($modoMasaje === 'extra')
                                            {{-- CATEGORÍA --}}
                                            <div class="input-field col s12 m6 l2">
                                            <select id="categoria_masaje_{{$i}}" name="masajes[{{$i}}][categoria_slug]">
                                                <option value="" disabled selected>-- Categoría --</option>
                                                @foreach($catalogoMasajes as $cat)
                                                <option value="{{ $cat['slug'] }}">{{ $cat['nombre'] }}</option>
                                                @endforeach
                                            </select>
                                            <label for="categoria_masaje_{{$i}}">Categoría</label>
                                            </div>

                                            {{-- TIPO --}}
                                            <div class="input-field col s12 m6 l2">
                                            <select id="tipo_masaje_{{$i}}" name="masajes[{{$i}}][tipo_slug]" disabled>
                                                <option value="" disabled selected>-- Tipo --</option>
                                            </select>
                                            <label for="tipo_masaje_{{$i}}">Tipo</label>
                                            </div>

                                            {{-- DURACIÓN / PRECIO --}}
                                            <div class="input-field col s12 m6 l2">
                                            <select id="precio_masaje_{{$i}}" name="masajes[{{$i}}][precio_id]" disabled>
                                                <option value="" disabled selected>-- Duración / Precio --</option>
                                            </select>
                                            <label for="precio_masaje_{{$i}}">Duración</label>
                                            <small id="info_precio_masaje_{{$i}}" class="grey-text"></small>
                                            </div>

                                        @else
                                            {{-- PROGRAMA: tipo fijo, tiempo_extra fijo --}}
                                            <input type="hidden" name="masajes[{{$i}}][tipo_masaje]" value="Relajación">
                                            <input type="hidden" name="masajes[{{$i}}][tiempo_extra]" value="0">
                                        @endif

                                        <div class="input-field col s12"></div>
                                    @endfor
                                    @endif

                                </div>
                            @endif




                            <div class="row">
                                <div class="input-field col s12 m6 l4">

                                    <label for="observacion">Observaciones - "Decoraciones"</label>
                                    <input id="observacion" type="text" name="observacion" class=""
                                        value="{{ old('observacion') }}">
                                    @error('observacion')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </div>


                                <div class="input-field col s12 m6 l4">
                                    <select name="id_ubicacion" id="id_ubicacion">
                                        <option value="" selected disabled="">-- Seleccione --</option>
                                        @foreach ($ubicaciones as $ubicacion)
                                        <option value="{{$ubicacion->id}}" {{ old('id_ubicacion') == $ubicacion->nombre ?
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

                                {{-- @if ($reserva->cantidad_personas <= 2)
                                    
                                
                                    <div class="input-field col s12 m6 l4" @if(!$hayMasajes) style="display:none;" @endif>
                                            <select name="id_lugar_masaje" id="id_lugar_masaje" @if(!$hayMasajes) disabled hidden @endif>
                                                
                                                @foreach ($lugares as $lugar)
                                                    <option value="{{$lugar->id}}" {{ old('id_lugar_masaje')==$lugar->nombre ?
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
                                @endif --}}

                            </div>



                            <div class="row">

                                <div class="col s12 m6 l4">
                                    <label for="trago_cortesia">Trago cortesia</label>
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
                            </div>





                            <div class="row"><br></div>
                            @if (!in_array('Almuerzo', $servicios) && !$almuerzosExtra)
                            <h6><strong> No registra almuerzos como servicios ni Extras</strong></h6>
                            @else
                            <div class="row">
                                <h6><strong> Menús por asistente</strong></h6>

                                @for ($i = 1; $i <= $reserva->cantidad_personas; $i++)

                                    <div class="input-field col s12 m6 l3">
                                        <select name="menus[{{ $i }}][id_producto_entrada]"
                                            id="id_producto_entrada_{{ $i }}">
                                            <option value="" disabled selected> -- Seleccione --</option>
                                            @foreach ($entradas as $entrada)
                                            <option value="{{$entrada->id}}" {{ old("menus.{$i}.id_producto_entrada") == $entrada->nombre ? 'selected'
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
                                            <option value="{{$fondo->id}}" {{ old("menus.{$i}.id_producto_fondo") == $fondo->nombre ? 'selected' : '' }}>{{$fondo->nombre}}</option>
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
                                            <option value="">Sin Acompañamiento</option>
                                            @foreach ($acompañamientos as $acompañamiento)
                                            <option value="{{$acompañamiento->id}}" {{ old("menus.{$i}.id_producto_acompanamiento") == $acompañamiento->nombre ? 'selected'
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
                                            class="" value="{{ old("menus.{$i}.alergias")}}">
                                        <label for="alergias_{{$i}}">Alérgias</label>
                                        @error('alergias_{{$i}}')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                    <div class="input-field col s12 m6 l2">
                                        <input type="text" name="menus[{{ $i }}][observacion]"
                                            id="observacion_{{ $i }}" value="{{ old("menus.{$i}.observacion") }}"/>
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


{{-- <script>
    $(document).ready(function () {
        // Cargar horarios desde el backend
        const horariosPorLugar = @json($horasMasaje);

        // // Inicializa Materialize para todos los selectores
        $('select').material_select();

        // Función para cargar horarios en horario_masaje según el lugar seleccionado
        function cargarHorariosUnico(lugarId) {
            const $horarioSelect = $('#horario_masaje');
            $horarioSelect.empty().append('<option value="" disabled selected>-- Seleccione --</option>');

            if (horariosPorLugar[lugarId]) {
                horariosPorLugar[lugarId].forEach(function (horario) {
                    $horarioSelect.append(new Option(horario, horario));
                });

                // Reinicializa Materialize para el selector
                // $horarioSelect.material_select();
            }
        }

        // Detectar cambios en el lugar de masaje
        $('#id_lugar_masaje').on('change', function () {
            const lugarId = $(this).val(); // ID del lugar seleccionado
            cargarHorariosUnico(lugarId); // Actualizar los horarios en horario_masaje
        });

        // Carga inicial: verifica si hay un lugar preseleccionado
        const lugarInicial = $('#id_lugar_masaje').val();
        if (lugarInicial) {
            cargarHorariosUnico(lugarInicial);
        }
    });

</script>


<script>

    $(document).ready(function () {
        // Cargar horarios desde el backend
        const horariosPorLugar = @json($horasMasaje);
        

        // Inicializa todos los selectores de Materialize
        $('select').material_select();

        // Función para cargar horarios en el select de horario masaje
        function cargarHorariosInicial(lugarId, index) {
            const $horarioSelect = $(`#horario_masaje_${index}`);
            $horarioSelect.empty().append('<option value="" disabled selected>-- Seleccione --</option>');

            if (horariosPorLugar[lugarId]) {
                horariosPorLugar[lugarId].forEach(function (horario) {
                    $horarioSelect.append(new Option(horario, horario));
                });

                // Reinicializa Materialize para el selector
                // $horarioSelect.material_select();
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

</script> --}}


<script>
$(document).ready(function () {
  var horariosPorLugar = @json($horasMasaje);

//   function refreshSelect($el){
//     try { $el.material_select('destroy'); } catch(e){}
//     $el.material_select();
//   }


    function refreshSelect($el){

        try { $el.material_select('destroy'); } catch(e){}
        $el.material_select();
    
    }


  // init general una sola vez
  $('select').material_select();

  // ===== singular (<=2) =====
  function cargarHorariosSingular(lugarId){
    var $horario = $('#horario_masaje');
    if (!$horario.length) return;

    $horario.empty().append('<option value="" disabled selected>-- Seleccione --</option>');
    (horariosPorLugar[lugarId] || []).forEach(function(h){
      $horario.append(new Option(h, h));
    });
    refreshSelect($horario);
  }

  $('#id_lugar_masaje').on('change', function(){
    cargarHorariosSingular($(this).val());
  });

  if ($('#id_lugar_masaje').length && $('#id_lugar_masaje').val()){
    cargarHorariosSingular($('#id_lugar_masaje').val());
  }

  // ===== multi (>=3 o programa) =====
  function cargarHorariosMulti(lugarId, index){
    var $horario = $('#horario_masaje_' + index);
    if (!$horario.length) return;

    $horario.empty().append('<option value="" disabled selected>-- Seleccione --</option>');
    (horariosPorLugar[lugarId] || []).forEach(function(h){
      $horario.append(new Option(h, h));
    });
    refreshSelect($horario);
  }

  $(document).on('change','[id^="id_lugar_masaje_"]', function(){
    var index = $(this).attr('id').split('_').pop();
    cargarHorariosMulti($(this).val(), index);
  });

  // carga inicial para todos los slots que ya existen
  $('[id^="id_lugar_masaje_"]').each(function(){
    var index = $(this).attr('id').split('_').pop();
    if ($(this).val()) cargarHorariosMulti($(this).val(), index);
  });
});
</script>




{{-- Alertas --}}
<script>

    @if(session('info'))
        Swal.fire({
            icon: 'info',
            title: 'Advertencia',
            text: '{{ session('info') }}',
            showConfirmButton: true,
            confirmButtonText: `Confirmar`,
            // timer: 5000,
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
            // timer: 5000,
        });
    @endif


</script>
        
<script>
    $(document).ready(function () {

        // ===== Catálogo completo desde backend =====
        var CATALOGO = @json($catalogoMasajes);

        function numberWithDots(n){
            return (n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
        }

        function matReinit(selector){
            setTimeout(
                function(){

                    try { $(selector).material_select('destroy'); } catch(e){}
                    $(selector).material_select();
                },0
            );
        }

        function getCategoriaBySlug(slug){
            for (var i=0;i<CATALOGO.length;i++){
            if (CATALOGO[i].slug === slug) return CATALOGO[i];
            }
            return null;
        }

        function getTipoFromCategoria(catSlug, tipoSlug){
            var cat = getCategoriaBySlug(catSlug);
            if (!cat) return null;
            var tipos = cat.tipos || [];
            for (var i=0;i<tipos.length;i++){
            if (tipos[i].slug === tipoSlug) return tipos[i];
            }
            return null;
        }

        function poblarTipos(index, catSlug, selectedTipoSlug){
            var $tipo = $('#tipo_masaje_'+index);
            var $precio = $('#precio_masaje_'+index);
            var $info = $('#info_precio_masaje_'+index);

            $tipo.prop('disabled', true).empty().append('<option value="" disabled selected>-- Tipo --</option>');
            $precio.prop('disabled', true).empty().append('<option value="" disabled selected>-- Duración / Precio --</option>');
            $info.text('');

            if (!catSlug) { matReinit($tipo); matReinit($precio); return; }

            var cat = getCategoriaBySlug(catSlug);
            if (!cat) { matReinit($tipo); matReinit($precio); return; }

            var tipos = cat.tipos || [];
            for (var i=0;i<tipos.length;i++){
            var t = tipos[i];
            var sel = (selectedTipoSlug && selectedTipoSlug === t.slug) ? ' selected' : '';
            $tipo.append('<option value="'+t.slug+'"'+sel+'>'+t.nombre+'</option>');
            }

            $tipo.prop('disabled', false);
            matReinit($tipo);
            matReinit($precio);
        }

        function poblarPrecios(index, catSlug, tipoSlug, selectedPrecioId){
            var $precio = $('#precio_masaje_'+index);
            var $info = $('#info_precio_masaje_'+index);

            $precio.prop('disabled', true).empty().append('<option value="" disabled selected>-- Duración / Precio --</option>');
            $info.text('');

            if (!catSlug || !tipoSlug) { matReinit($precio); return; }

            var tipo = getTipoFromCategoria(catSlug, tipoSlug);
            if (!tipo) { matReinit($precio); return; }

            var precios = tipo.precios || [];
            for (var i=0;i<precios.length;i++){
            var p = precios[i];
            var label = p.duracion_minutos + ' min — $' + numberWithDots(p.precio_unitario);
            if (p.precio_pareja !== null) {
                label += ' (2x: $' + numberWithDots(p.precio_pareja) + ')';
            }
            var sel = (selectedPrecioId && parseInt(selectedPrecioId,10) === parseInt(p.id,10)) ? ' selected' : '';
            $precio.append('<option value="'+p.id+'"'+sel+'>'+label+'</option>');
            }

            $precio.prop('disabled', false);
            matReinit($precio);
        }

        function mostrarInfoPrecio(index, catSlug, tipoSlug, precioId){
            var $info = $('#info_precio_masaje_'+index);
            $info.text('');

            if (!catSlug || !tipoSlug || !precioId) return;

            var tipo = getTipoFromCategoria(catSlug, tipoSlug);
            if (!tipo) return;

            var precios = tipo.precios || [];
            for (var i=0;i<precios.length;i++){
                if (parseInt(precios[i].id,10) === parseInt(precioId,10)) {
                    var p = precios[i];
                    var txt = 'Seleccionado: ' + tipo.nombre + ' • ' + p.duracion_minutos + ' min • $' + numberWithDots(p.precio_unitario);
                    if (p.precio_pareja !== null) {
                    txt += ' • 2x: $' + numberWithDots(p.precio_pareja);
                    }
                    $info.text(txt);
                    return;
                }
            }
        }

        // ======= BINDINGS PARA TODOS LOS INDICES EXISTENTES =======
        // Busca selects de categoría: categoria_masaje_1, categoria_masaje_2, etc.
        $('[id^="categoria_masaje_"]').each(function(){
            var index = $(this).attr('id').split('_').pop();

            // valores old() si existen
            var oldCat   = $('#categoria_masaje_'+index).val();
            var oldTipo  = $('#tipo_masaje_'+index).data('old') || null;     // opcional si quieres setear data-old
            var oldPrecio= $('#precio_masaje_'+index).data('old') || null;   // opcional

            // Si hay categoría preseleccionada (old), pobla tipos.
            if (oldCat) {
                poblarTipos(index, oldCat, oldTipo);
                // si además hay tipo seleccionado (por old), pobla precios
                var currentTipo = $('#tipo_masaje_'+index).val();
                if (currentTipo) {
                    poblarPrecios(index, oldCat, currentTipo, oldPrecio);
                    var currentPrecio = $('#precio_masaje_'+index).val();
                    if (currentPrecio) {
                    mostrarInfoPrecio(index, oldCat, currentTipo, currentPrecio);
                    }
                }
            } else {
                matReinit('#tipo_masaje_'+index);
                matReinit('#precio_masaje_'+index);
            }

            // Cambia categoría -> carga tipos y limpia precios
            $('#categoria_masaje_'+index).on('change', function(){
                var catSlug = $(this).val();
                poblarTipos(index, catSlug, null);
            });

            // Cambia tipo -> carga precios
            $('#tipo_masaje_'+index).on('change', function(){
                var catSlug = $('#categoria_masaje_'+index).val();
                var tipoSlug = $(this).val();
                poblarPrecios(index, catSlug, tipoSlug, null);
            });

            // Cambia precio -> muestra info
            $('#precio_masaje_'+index).on('change', function(){
                var catSlug = $('#categoria_masaje_'+index).val();
                var tipoSlug = $('#tipo_masaje_'+index).val();
                var precioId = $(this).val();
                mostrarInfoPrecio(index, catSlug, tipoSlug, precioId);
            });
        });








        // === Binding singular (<=2) ===
        if ($('#categoria_masaje').length) {

            var oldTipo   = $('#tipo_masaje').data('old') || null;
            var oldPrecio = $('#precio_masaje').data('old') || null;

            // Si hay categoría ya seleccionada por old(), poblar tipos y precios
            var catSlug = $('#categoria_masaje').val();
            if (catSlug) {
                poblarTiposSingular(catSlug, oldTipo);
                var tipoSlug = $('#tipo_masaje').val();
                if (tipoSlug) {
                    poblarPreciosSingular(catSlug, tipoSlug, oldPrecio);
                    var precioId = $('#precio_masaje').val();
                    if (precioId) mostrarInfoPrecioSingular(catSlug, tipoSlug, precioId);
                }
            } else {
                matReinit('#tipo_masaje');
                matReinit('#precio_masaje');
            }

            $('#categoria_masaje').on('change', function(){
                poblarTiposSingular($(this).val(), null);
            });

            $('#tipo_masaje').on('change', function(){
                poblarPreciosSingular($('#categoria_masaje').val(), $(this).val(), null);
            });

            $('#precio_masaje').on('change', function(){
                mostrarInfoPrecioSingular($('#categoria_masaje').val(), $('#tipo_masaje').val(), $(this).val());
            });
        }

        // ===== Helpers singular reutilizando tus funciones base =====
        function poblarTiposSingular(catSlug, selectedTipoSlug){
            // usa tu lógica, pero apuntando a #tipo_masaje y #precio_masaje
            var $tipo = $('#tipo_masaje');
            var $precio = $('#precio_masaje');
            var $info = $('#info_precio_masaje');

            $tipo.prop('disabled', true).empty().append('<option value="" disabled selected>-- Tipo --</option>');
            $precio.prop('disabled', true).empty().append('<option value="" disabled selected>-- Duración / Precio --</option>');
            $info.text('');

            if (!catSlug) { matReinit($tipo); matReinit($precio); return; }

            var cat = getCategoriaBySlug(catSlug);
            if (!cat) { matReinit($tipo); matReinit($precio); return; }

            var tipos = cat.tipos || [];
            for (var i=0;i<tipos.length;i++){
                var t = tipos[i];
                var sel = (selectedTipoSlug && selectedTipoSlug === t.slug) ? ' selected' : '';
                $tipo.append('<option value="'+t.slug+'"'+sel+'>'+t.nombre+'</option>');
            }

            $tipo.prop('disabled', false);
            matReinit($tipo);
            matReinit($precio);
        }

        function poblarPreciosSingular(catSlug, tipoSlug, selectedPrecioId){
            var $precio = $('#precio_masaje');
            var $info = $('#info_precio_masaje');

            $precio.prop('disabled', true).empty().append('<option value="" disabled selected>-- Duración / Precio --</option>');
            $info.text('');

            if (!catSlug || !tipoSlug) { matReinit($precio); return; }

            var tipo = getTipoFromCategoria(catSlug, tipoSlug);
            if (!tipo) { matReinit($precio); return; }

            var precios = tipo.precios || [];
            for (var i=0;i<precios.length;i++){
                var p = precios[i];
                var label = p.duracion_minutos + ' min — $' + numberWithDots(p.precio_unitario);
                if (p.precio_pareja !== null) label += ' (2x: $' + numberWithDots(p.precio_pareja) + ')';

                var sel = (selectedPrecioId && parseInt(selectedPrecioId,10) === parseInt(p.id,10)) ? ' selected' : '';
                $precio.append('<option value="'+p.id+'"'+sel+'>'+label+'</option>');
            }

            $precio.prop('disabled', false);
            matReinit($precio);
        }

        function mostrarInfoPrecioSingular(catSlug, tipoSlug, precioId){
            // igual a la tuya, pero escribe en #info_precio_masaje
            var $info = $('#info_precio_masaje');
            $info.text('');
            if (!catSlug || !tipoSlug || !precioId) return;

            var tipo = getTipoFromCategoria(catSlug, tipoSlug);
            if (!tipo) return;

            var precios = tipo.precios || [];
            for (var i=0;i<precios.length;i++){
                if (parseInt(precios[i].id,10) === parseInt(precioId,10)) {
                    var p = precios[i];
                    var txt = 'Seleccionado: ' + tipo.nombre + ' • ' + p.duracion_minutos + ' min • $' + numberWithDots(p.precio_unitario);
                    if (p.precio_pareja !== null) txt += ' • 2x: $' + numberWithDots(p.precio_pareja);
                    $info.text(txt);
                    return;
                }
            }
        }

    });


</script>


{{-- <script>
    $(document).ready(function () {
        var horariosPorLugar = @json($horasMasaje);

        function reinitSelect($el){
            try { $el.material_select('destroy'); } catch(e){}
            $el.material_select();
        }

        // === SINGULAR ===
        function cargarHorariosSingular(lugarId){
            var $horario = $('#horario_masaje');
            if (!$horario.length) return;

            $horario.empty().append('<option value="" disabled selected>-- Seleccione --</option>');
            if (horariosPorLugar[lugarId]) {
                horariosPorLugar[lugarId].forEach(function(h){
                    $horario.append(new Option(h, h));
                });
            }
            reinitSelect($horario);
        }

        $('#id_lugar_masaje').on('change', function(){
            cargarHorariosSingular($(this).val());
        });

        if ($('#id_lugar_masaje').length && $('#id_lugar_masaje').val()) {
            cargarHorariosSingular($('#id_lugar_masaje').val());
        }

        // === MULTI ===
        function cargarHorariosMulti(lugarId, index){
            var $horario = $('#horario_masaje_' + index);
            if (!$horario.length) return;

            $horario.empty().append('<option value="" disabled selected>-- Seleccione --</option>');
            if (horariosPorLugar[lugarId]) {
                horariosPorLugar[lugarId].forEach(function(h){
                    $horario.append(new Option(h, h));
                });
            }
            reinitSelect($horario);
        }

        $('[id^="id_lugar_masaje_"]').on('change', function(){
            var index = $(this).attr('id').split('_').pop();
            cargarHorariosMulti($(this).val(), index);
        });

        $('[id^="id_lugar_masaje_"]').each(function(){
            var index = $(this).attr('id').split('_').pop();
            if ($(this).val()) cargarHorariosMulti($(this).val(), index);
        });

        // Init Materialize UNA vez
        $('select').material_select();
    });
</script> --}}


@endsection