@extends('themes.backoffice.layouts.admin')

@section('title','Modificar Visita')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $reserva) }}">Reserva del cliente</a></li>
<li>Modificar Visita</li>
@endsection



@section('content')
<div class="section">
    <p class="caption">La reserva fue modificada, revisa y actualiza los datos de la visita si corresponde</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m10 offset-m1 ">
                <div class="card-panel">
                    <h4 class="header">Modificar visita para <strong>{{$reserva->cliente->nombre_cliente}}</strong> -
                        Fecha:<strong>{{$reserva->fecha_visita}}</strong></h4>
                    <div class="row">

                        @php
                            $hayMasajes = !is_null($modoMasaje);

                            // Personas/masajes reales según modo
                            if ($modoMasaje === 'extra') {
                                $personasMasajeView = (int) $cantidadMasajesExtra; // 1 masaje = 1 persona lógica
                            } elseif ($modoMasaje === 'programa') {
                                $personasMasajeView = (int) $reserva->cantidad_personas; // programa: masaje por persona
                            } else {
                                $personasMasajeView = 0;
                            }

                            // Slots: ya vienen calculados desde el controller (fuente de verdad)
                            $slotsMasaje = (int) $cantidadSlotsMasaje;

                            $indexSpa = (int) ceil($reserva->cantidad_personas / 5);
                        @endphp


                        <form class="col s12" method="post"
                            action="{{route('backoffice.reserva.visitas.actualizar', $reserva)}}">

                            {{csrf_field() }}
                            {{method_field('PUT')}}


                            @if ($reserva->cantidad_personas <= 2)
                                    <div class="row">

                                        <div class="input-field col s12 m6 l4">
                                            <select name="horario_sauna" id="horario_sauna">
                                                <option value="" @unless($visita->horario_sauna) selected @endunless disabled>-- Seleccione --</option>
                                                @if($visita->horario_sauna)
                                                <option value="{{ $visita->horario_sauna }}" {{ old('horario_sauna', $visita->horario_sauna) === $visita->horario_sauna ? 'selected' : '' }}>{{ $visita->horario_sauna }} (actual)</option>
                                                @endif
                                                @foreach($horarios as $horario)
                                                    <option value="{{ $horario }}" {{ old('horario_sauna')==$horario ? 'selected' : '' }}>{{ $horario }}</option>
                                                @endforeach
                                            </select>
                                            <label for="horario_sauna">Horario SPA</label>
                                        </div>

                                        @if($hayMasajes)
                                            <div class="col s12"><h6><strong>Masajes</strong></h6></div>

                                            @for($i=1; $i <= $slotsMasaje; $i++)
                                                @php $masajeActual = $masajesActuales[$i-1] ?? null; @endphp

                                                {{-- HORARIO --}}
                                                <div class="input-field col s12 m6 l2">
                                                    <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]">
                                                        <option value="" @unless(optional($masajeActual)->horario_masaje) selected @endunless disabled>-- Seleccione --</option>
                                                        @if(optional($masajeActual)->horario_masaje)
                                                        <option value="{{ $masajeActual->horario_masaje }}" selected>{{ $masajeActual->horario_masaje }} (actual)</option>
                                                        @endif
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
                                                            <option value="{{ $lugar->id }}" {{ optional($masajeActual)->id_lugar_masaje === $lugar->id ? 'selected' : '' }}>{{ $lugar->nombre }}</option>
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
                                            <option value="" @unless($visita->horario_sauna) selected @endunless disabled>-- Seleccione --</option>
                                            @if($visita->horario_sauna)
                                            <option value="{{ $visita->horario_sauna }}" selected>{{ $visita->horario_sauna }} (actual)</option>
                                            @endif
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
                                        @php
                                            $masajeActual = $modoMasaje === 'extra'
                                                ? ($masajesActuales[$i-1] ?? null)
                                                : ($masajesActuales[($i-1)*2] ?? null);
                                        @endphp

                                        {{-- HORARIO (siempre) --}}
                                        <div class="input-field col s12 m6 l2">
                                        <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]">
                                            <option value="" @unless(optional($masajeActual)->horario_masaje) selected @endunless disabled>-- Seleccione --</option>
                                            @if(optional($masajeActual)->horario_masaje)
                                            <option value="{{ $masajeActual->horario_masaje }}" selected>{{ $masajeActual->horario_masaje }} (actual)</option>
                                            @endif
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
                                            <option value="{{$lugar->id}}" {{ optional($masajeActual)->id_lugar_masaje === $lugar->id ? 'selected' : '' }}>{{$lugar->nombre}}</option>
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
                                        @php $visitaActual = $visitasActuales[$i-1] ?? null; @endphp
                                    <div class="input-field col s12 m6 l4">
                                        <h6>Grupo {{$i}}</h6>
                                        <select id="horario_sauna_{{$i}}" name="spas[{{$i}}][horario_sauna]">
                                            <option value="" @unless(optional($visitaActual)->horario_sauna) selected @endunless disabled>-- Seleccione --</option>
                                            @if(optional($visitaActual)->horario_sauna)
                                            <option value="{{ $visitaActual->horario_sauna }}" selected>{{ $visitaActual->horario_sauna }} (actual)</option>
                                            @endif
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
                                        @php
                                            $masajeActual = $modoMasaje === 'extra'
                                                ? ($masajesActuales[$i-1] ?? null)
                                                : ($masajesActuales[($i-1)*2] ?? null);
                                        @endphp

                                        {{-- HORARIO (siempre) --}}
                                        <div class="input-field col s12 m6 l2">
                                        <select id="horario_masaje_{{$i}}" name="masajes[{{$i}}][horario_masaje]">
                                            <option value="" @unless(optional($masajeActual)->horario_masaje) selected @endunless disabled>-- Seleccione --</option>
                                            @if(optional($masajeActual)->horario_masaje)
                                            <option value="{{ $masajeActual->horario_masaje }}" selected>{{ $masajeActual->horario_masaje }} (actual)</option>
                                            @endif
                                        </select>
                                        <label for="horario_masaje_{{$i}}">Horario Masaje</label>
                                        </div>

                                        {{-- LUGAR (siempre) --}}
                                        <div class="input-field col s12 m6 l2">
                                        <select name="masajes[{{$i}}][id_lugar_masaje]" id="id_lugar_masaje_{{$i}}">
                                            @foreach($lugares as $lugar)
                                            <option value="{{$lugar->id}}" {{ optional($masajeActual)->id_lugar_masaje === $lugar->id ? 'selected' : '' }}>{{$lugar->nombre}}</option>
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
                                        value="{{ old('observacion', $visita->observacion) }}">
                                    @error('observacion')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </div>


                                <div class="input-field col s12 m6 l4">
                                    <select name="id_ubicacion" id="id_ubicacion">
                                        <option value="" @unless($visita->id_ubicacion) selected @endunless disabled>-- Seleccione --</option>
                                        @if($visita->id_ubicacion)
                                        <option value="{{ $visita->id_ubicacion }}" selected>{{ optional($visita->ubicacion)->nombre }} (actual)</option>
                                        @endif
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

                            </div>



                            <div class="row">

                                <div class="col s12 m6 l4">
                                    <label for="trago_cortesia">Trago cortesia</label>
                                    <p>
                                        <label>
                                            <input name="trago_cortesia" id="trago_cortesia" type="radio"
                                                class="with-gap" value="Si" {{ $visita->trago_cortesia === 'Si' ? 'checked' : '' }}>
                                            <span class="black-text">Si</span>
                                        </label>

                                        <label>
                                            <input name="trago_cortesia" id="trago_cortesia" type="radio"
                                                class="with-gap" value="No" {{ $visita->trago_cortesia === 'No' ? 'checked' : '' }}/>
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
                                    @php $menuActual = $menusActuales[$i-1] ?? null; @endphp

                                    <div class="input-field col s12 m6 l3">
                                        <select name="menus[{{ $i }}][id_producto_entrada]"
                                            id="id_producto_entrada_{{ $i }}">
                                            <option value="" disabled selected> -- Seleccione --</option>
                                            @foreach ($entradas as $entrada)
                                            <option value="{{$entrada->id}}" {{ optional($menuActual)->id_producto_entrada === $entrada->id ? 'selected'
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
                                            <option value="{{$fondo->id}}" {{ optional($menuActual)->id_producto_fondo === $fondo->id ? 'selected' : '' }}>{{$fondo->nombre}}</option>
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
                                            <option value="" {{ is_null(optional($menuActual)->id_producto_acompanamiento) ? 'selected' : '' }}>Sin Acompañamiento</option>
                                            @foreach ($acompañamientos as $acompañamiento)
                                            <option value="{{$acompañamiento->id}}" {{ optional($menuActual)->id_producto_acompanamiento === $acompañamiento->id ? 'selected'
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
                                            class="" value="{{ optional($menuActual)->alergias }}">
                                        <label for="alergias_{{$i}}">Alérgias</label>
                                        @error('alergias_{{$i}}')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                    <div class="input-field col s12 m6 l2">
                                        <input type="text" name="menus[{{ $i }}][observacion]"
                                            id="observacion_{{ $i }}" value="{{ optional($menuActual)->observacion }}"/>
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

                            @php
                                $incluyeAmbosBuffet = in_array('Desayuno y Once', $servicios);
                                $incluyeUnoBuffet   = in_array('Desayuno u Once', $servicios);
                            @endphp

                            @if ($incluyeAmbosBuffet)
                            <div class="row">
                                <div class="col s12">
                                    <h6><strong>Desayuno y Once</strong></h6>
                                    <p>El programa incluye ambos servicios para los {{ $reserva->cantidad_personas }} asistentes (conteo automático, no requiere selección).</p>
                                </div>
                            </div>
                            @elseif ($incluyeUnoBuffet)
                            <div class="row">
                                <div class="col s12">
                                    <h6><strong>Desayuno u Once</strong></h6>
                                    <p>Seleccione la opción para los {{ $reserva->cantidad_personas }} asistentes:</p>
                                    <label>
                                        <input name="desayuno_once" type="radio" class="with-gap" value="desayuno" required
                                            {{ old('desayuno_once', $tipoDesayunoOnceActual) === 'desayuno' ? 'checked' : '' }} />
                                        <span class="black-text">Desayuno</span>
                                    </label>
                                    <label style="margin-left: 20px;">
                                        <input name="desayuno_once" type="radio" class="with-gap" value="once" required
                                            {{ old('desayuno_once', $tipoDesayunoOnceActual) === 'once' ? 'checked' : '' }} />
                                        <span class="black-text">Once</span>
                                    </label>
                                    @error('desayuno_once')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            <div class="row">
                                <div class="input-field col s12">
                                    <button id="btn-actualizar" class="btn waves-effect waves-light right" type="submit">Actualizar
                                        <i class="material-icons right">update</i>
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
  $(document).ready(function () {
    $('form').on('submit', function (){
      const $btn = $('#btn-actualizar');
      $btn.prop('disabled', true);
      $btn.html('<i class="material-icons left">hourglass_empty</i>Guardando...');
    });
  });
</script>


<script>
$(document).ready(function () {
  var horariosPorLugar = @json($horasMasaje);

  function refreshSelect($el){
    try { $el.material_select('destroy'); } catch(e){}
    $el.material_select();
  }

  // init general una sola vez
  $('select').material_select();

  // ===== masajes: agrega horarios disponibles a cada select, conservando la opción "actual" =====
  function cargarHorariosMasaje(lugarId, index){
    var $horario = $('#horario_masaje_' + index);
    if (!$horario.length) return;

    (horariosPorLugar[lugarId] || []).forEach(function(h){
      $horario.append(new Option(h, h));
    });
    refreshSelect($horario);
  }

  $(document).on('change','[id^="id_lugar_masaje_"]', function(){
    var index = $(this).attr('id').split('_').pop();
    var $horario = $('#horario_masaje_' + index);
    $horario.empty().append('<option value="" disabled selected>-- Seleccione --</option>');
    cargarHorariosMasaje($(this).val(), index);
  });

  // carga inicial: agrega los horarios disponibles a continuación de la opción actual
  $('[id^="id_lugar_masaje_"]').each(function(){
    var index = $(this).attr('id').split('_').pop();
    if ($(this).val()) cargarHorariosMasaje($(this).val(), index);
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

<script>
    $(document).ready(function () {

        // ===== Catálogo completo desde backend (modo "extra") =====
        var CATALOGO = @json($catalogoMasajes);

        function numberWithDots(n){
            return (n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
        }

        function matReinit(selector){
            setTimeout(function(){
                try { $(selector).material_select('destroy'); } catch(e){}
                $(selector).material_select();
            },0);
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

        function poblarTipos(index, catSlug){
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
            $tipo.append('<option value="'+t.slug+'">'+t.nombre+'</option>');
            }

            $tipo.prop('disabled', false);
            matReinit($tipo);
            matReinit($precio);
        }

        function poblarPrecios(index, catSlug, tipoSlug){
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
            $precio.append('<option value="'+p.id+'">'+label+'</option>');
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

        $('[id^="categoria_masaje_"]').each(function(){
            var index = $(this).attr('id').split('_').pop();

            $('#categoria_masaje_'+index).on('change', function(){
                poblarTipos(index, $(this).val());
            });

            $('#tipo_masaje_'+index).on('change', function(){
                var catSlug = $('#categoria_masaje_'+index).val();
                poblarPrecios(index, catSlug, $(this).val());
            });

            $('#precio_masaje_'+index).on('change', function(){
                var catSlug = $('#categoria_masaje_'+index).val();
                var tipoSlug = $('#tipo_masaje_'+index).val();
                mostrarInfoPrecio(index, catSlug, tipoSlug, $(this).val());
            });
        });

    });
</script>
@endsection
