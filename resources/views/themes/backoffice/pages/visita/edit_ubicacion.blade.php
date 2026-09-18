@extends('themes.backoffice.layouts.admin')

@section('title', '')

@section('head')
@endsection


@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear
        Reserva</a></li> --}}
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Modificar Ubicacion</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header2">Cambiar de Ubicacion <strong>{{$visita->ubicacion->nombre ?? 'No registra ubicacion'}}</strong></h4>
                    <div class="row">



                        {{-- CONTENIDO --}}

                        <form class="col s10 offset-m2" method="post" action="{{route('backoffice.visita.update_ubicacion', $visita)}}">

                            @csrf
                            @method('PUT')

                            @if(optional($reserva->programa)->espacio_tipo === 'wellness')
                            <div class="row">
                                <h6><strong>Terraza o Reposera</strong></h6>
                                <div class="col s12">
                                    <p>Seleccione la preferencia para poder asignar la ubicación:</p>
                                    <label>
                                        <input name="wellness" id="wellness_terraza" type="radio" class="with-gap" value="terraza"
                                            {{ old('wellness', $wellnessActual) === 'terraza' ? 'checked' : '' }} />
                                        <span class="black-text">Terraza</span>
                                    </label>
                                    <label style="margin-left: 20px;">
                                        <input name="wellness" id="wellness_reposera" type="radio" class="with-gap" value="reposera"
                                            {{ old('wellness', $wellnessActual) === 'reposera' ? 'checked' : '' }} />
                                        <span class="black-text">Reposera</span>
                                    </label>
                                    @error('wellness')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row"><br></div>
                            @endif

                            <div id="ubicaciones_wrapper" class="row">
                                <h6><strong>Ubicaciones Asignadas</strong></h6>

                                <p id="ubicaciones_hint" class="grey-text col s12" style="display:none;">
                                    Selecciona Terraza o Reposera para poder asignar la ubicación.
                                </p>

                                <div id="ubicaciones_filas"></div>

                                <div class="col s12" style="margin-top: 8px;">
                                    <a href="#" id="btn_agregar_ubicacion" class="btn-flat waves-effect">
                                        <i class="material-icons left">add</i>Agregar ubicación
                                    </a>
                                    <span id="ubicaciones_contador" class="grey-text" style="margin-left: 16px;"></span>
                                </div>

                                @error('ubicaciones')
                                <div class="col s12">
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                </div>
                                @enderror
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
$(document).ready(function () {
  var UBICACIONES = @json($ubicaciones);
  var ASIGNADAS = @json($ubicacionesAsignadas);
  var CANTIDAD_PERSONAS = {{ (int) $reserva->cantidad_personas }};
  var ES_WELLNESS = {{ optional($reserva->programa)->espacio_tipo === 'wellness' ? 'true' : 'false' }};

  var $filas = $('#ubicaciones_filas');
  var filaIndex = 0;

  function subTiposPermitidos() {
    if (!ES_WELLNESS) { return null; }
    var pref = $('input[name=wellness]:checked').val();
    if (pref === 'terraza') { return ['terraza', 'estacion_grupal']; }
    if (pref === 'reposera') { return ['reposera']; }
    return [];
  }

  function ubicacionesFiltradas() {
    var permitidos = subTiposPermitidos();
    if (permitidos === null) { return UBICACIONES; }
    return UBICACIONES.filter(function (u) { return permitidos.indexOf(u.sub_tipo) !== -1; });
  }

  function actualizarVisibilidadUbicaciones() {
    if (!ES_WELLNESS) { return; }
    if ($('input[name=wellness]:checked').length) {
      $('#ubicaciones_wrapper').children().not('#ubicaciones_hint').show();
      $('#ubicaciones_hint').hide();
    } else {
      $('#ubicaciones_wrapper').children().not('#ubicaciones_hint').hide();
      $('#ubicaciones_hint').show();
    }
  }

  function opcionesHtml(seleccionId) {
    var html = '<option value="" disabled ' + (seleccionId ? '' : 'selected') + '>-- Seleccione --</option>';
    ubicacionesFiltradas().forEach(function (u) {
      var sel = (seleccionId && parseInt(seleccionId, 10) === u.id) ? 'selected' : '';
      html += '<option value="' + u.id + '" ' + sel + '>' + u.nombre + '</option>';
    });
    return html;
  }

  function agregarFila(ubicacionId, personas) {
    var i = filaIndex++;
    var html =
        '<div class="input-field col s12 m6" data-fila="' + i + '">' +
          '<select name="ubicaciones[' + i + '][id]" id="ubicacion_select_' + i + '">' +
            opcionesHtml(ubicacionId) +
          '</select>' +
          '<label>Ubicación</label>' +
        '</div>' +
        '<div class="input-field col s12 m4 l3" data-fila="' + i + '">' +
          '<input type="number" min="1" name="ubicaciones[' + i + '][personas]" id="ubicacion_personas_' + i + '" value="' + (personas || '') + '">' +
          '<label class="active">Personas</label>' +
        '</div>' +
        '<div class="col s12 m2 l1" data-fila="' + i + '" style="margin-top: 1rem;">' +
          '<a href="#" class="btn-flat red-text quitar-ubicacion" style="margin-top: 20px;"><i class="material-icons">delete</i></a>' +
        '</div>';

    $filas.append(html);
    (function (indice) {
      $('#ubicacion_select_' + indice).material_select(function () {
        var ubicacionId = parseInt($('#ubicacion_select_' + indice).val(), 10);
        var ubicacion = UBICACIONES.find(function (u) { return u.id === ubicacionId; });
        if (ubicacion && ubicacion.capacidad_max) {
          $('#ubicacion_personas_' + indice).val(ubicacion.capacidad_max);
        }
        actualizarContador();
      });
    })(i);
    var $wrapper = $('#ubicacion_select_' + i).parent('.select-wrapper');
    if ($wrapper.length) {
      $wrapper[0].addEventListener('click', function (e) { e.stopPropagation(); });
    }
    actualizarContador();
  }

  $('#btn_agregar_ubicacion').on('click', function (e) {
    e.preventDefault();
    agregarFila(null, null);
  });

  $filas.on('click', '.quitar-ubicacion', function (e) {
    e.preventDefault();
    var fila = $(this).closest('[data-fila]').data('fila');
    $filas.find('[data-fila="' + fila + '"]').remove();
    if ($filas.children().length === 0) {
      agregarFila(null, null);
    }
    actualizarContador();
  });

  $filas.on('change', 'select', function () {
    var fila = $(this).closest('[data-fila]').data('fila');
    var ubicacionId = parseInt($(this).val(), 10);
    var ubicacion = UBICACIONES.find(function (u) { return u.id === ubicacionId; });
    if (ubicacion && ubicacion.capacidad_max) {
      $filas.find('input[data-fila="' + fila + '"]').val(ubicacion.capacidad_max);
    }
    actualizarContador();
  });

  $filas.on('input', 'input[type=number]', function () {
    actualizarContador();
  });

  function actualizarContador() {
    var total = 0;
    $filas.find('input[type=number]').each(function () {
      total += parseInt($(this).val(), 10) || 0;
    });
    $('#ubicaciones_contador').text(total + ' / ' + CANTIDAD_PERSONAS + ' personas asignadas');
  }

  $('input[name=wellness]').on('change', function () {
    $filas.empty();
    filaIndex = 0;
    actualizarVisibilidadUbicaciones();
    setTimeout(function () {
      agregarFila(null, CANTIDAD_PERSONAS);
    }, 0);
  });

  actualizarVisibilidadUbicaciones();

  if (!ES_WELLNESS || $('input[name=wellness]:checked').length) {
    if (ASIGNADAS.length > 0) {
      ASIGNADAS.forEach(function (a) {
        agregarFila(a.id, a.personas);
      });
    } else {
      agregarFila(null, CANTIDAD_PERSONAS);
    }
  }
});
</script>
@endsection