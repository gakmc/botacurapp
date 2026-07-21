@extends('themes.backoffice.layouts.admin')

@section('title','Modificar Menús')

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.show', $cliente->id) }}">Reservas del cliente</a></li>
<li>Crear Reserva</li> --}}
@endsection

@section('content')
<div class="section">
  <p class="caption">Introduce los datos para Modificar los Menú</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
      <div class="row">
          <div class="col s12 m10 offset-m1 ">
              <div class="card-panel">
                  <h4 class="header">Modificar Menú para <strong>{{$reserva->cliente->nombre_cliente}}</strong> -
                      Fecha:<strong>{{$reserva->fecha_visita}}</strong></h4>
                  <div class="row">
                      <form class="col s12" method="post"
                          action="{{route('backoffice.reserva.menu_update', $reserva)}}">


                          {{csrf_field() }}
                          @method('PUT')


                          <div class="row"><br></div>
                          @if (!in_array('Almuerzo', $servicios) && !$almuerzosExtra)
                          <h6><strong> No registra almuerzos como servicios ni Extras</strong></h6>
                          @else

                          <h6><strong>Menús por asistente</strong></h6>

                          @php $menuIndex = 1; @endphp
                          @foreach ($menus as $menu)
                          <div class="row" style="border-bottom:1px solid #eee; padding-bottom:4px; margin-bottom:8px;">

                              {{-- Etiqueta menú N --}}
                              <div class="col s12" style="padding-bottom:0; margin-bottom:-6px;">
                                  <small class="grey-text">Menú {{ $menuIndex }}</small>
                              </div>

                              <div class="input-field col s12 m6 l3">
                                  <select name="menus[{{ $menu->id }}][id_producto_entrada]"
                                      id="id_producto_entrada_{{ $menu->id }}">
                                      <option value="" disabled selected> -- Seleccione --</option>
                                      @foreach ($entradas as $entrada)
                                      <option value="{{$entrada->id}}" {{ $menu->id_producto_entrada === $entrada->id ? 'selected' : '' }}>{{$entrada->nombre}}</option>
                                      @endforeach
                                  </select>
                                  <label for="id_producto_entrada_{{ $menu->id }}">Entrada</label>
                                  @error('menus.'.$menu->id.'.id_producto_entrada')
                                  <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                  @enderror
                              </div>

                              <div class="input-field col s12 m6 l3">
                                  <select name="menus[{{$menu->id}}][id_producto_fondo]" id="id_producto_fondo_{{$menu->id}}">
                                      <option value="" disabled selected> -- Seleccione --</option>
                                      @foreach ($fondos as $fondo)
                                      <option value="{{$fondo->id}}" {{ $menu->id_producto_fondo === $fondo->id ? 'selected' : '' }}>{{$fondo->nombre}}</option>
                                      @endforeach
                                  </select>
                                  <label for="id_producto_fondo_{{ $menu->id }}">Fondo</label>
                                  @error('menus.'.$menu->id.'.id_producto_fondo')
                                  <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                  @enderror
                              </div>

                              <div class="input-field col s12 m6 l2">
                                  <select name="menus[{{$menu->id}}][id_producto_acompanamiento]"
                                      id="id_producto_acompanamiento_{{$menu->id}}">
                                      <option value="" disabled selected> -- Seleccione --</option>
                                      <option value="" {{ $menu->id_producto_acompanamiento === null ? 'selected' : '' }}>Sin Acompañamiento</option>
                                      @foreach ($acompañamientos as $acompañamiento)
                                      <option value="{{$acompañamiento->id}}" {{ $menu->id_producto_acompanamiento === $acompañamiento->id ? 'selected' : '' }}>{{$acompañamiento->nombre}}</option>
                                      @endforeach
                                  </select>
                                  <label for="id_producto_acompanamiento_{{ $menu->id }}">Acompañamiento</label>
                                  @error('menus.'.$menu->id.'.id_producto_acompanamiento')
                                  <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                  @enderror
                              </div>

                              <div class="input-field col s12 m6 l2">
                                  <input id="alergias_{{$menu->id}}" type="text" name="menus[{{ $menu->id }}][alergias]" value="{{ $menu->alergias }}">
                                  <label for="alergias_{{$menu->id}}">Alérgias</label>
                                  @error('menus.'.$menu->id.'.alergias')
                                  <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                  @enderror
                              </div>

                              <div class="input-field col s12 m6 l2">
                                  <input type="text" name="menus[{{ $menu->id }}][observacion]"
                                      id="observacion_{{ $menu->id }}" value="{{ $menu->observacion }}"/>
                                  <label for="observacion_{{$menu->id}}">Observaciones</label>
                                  @error('menus.'.$menu->id.'.observacion')
                                  <span class="invalid-feedback" role="alert"><strong style="color:red">{{ $message }}</strong></span>
                                  @enderror
                              </div>

                          </div>
                          @php $menuIndex++; @endphp
                          @endforeach

                          {{-- Selector tipo servicio: UNO para toda la reserva --}}
                          @if($mostrarSelectTipoServicio ?? false)
                          @php
                              $tipoServicioActual = $menus->first()->tipo_servicio ?? null;
                              $cntDesayunoMenu = $menus->whereIn('tipo_servicio', ['desayuno', 'desayuno_y_once'])->count();
                              $cntOnceMenu     = $menus->whereIn('tipo_servicio', ['once', 'desayuno_y_once'])->count();
                          @endphp
                          <div class="row" style="margin-top:16px;">
                              <div class="col s12 m6 l4">
                                  <ul class="collection">
                                      <li class="collection-item avatar">
                                          <i class="material-icons circle blue">free_breakfast</i>
                                          <span class="title"><strong>Desayuno / Once</strong></span>
                                          <p style="margin-bottom:6px;">Elección para toda la reserva</p>
                                          <div class="input-field" style="margin-top:0;">
                                              <select name="tipo_servicio" id="tipo_servicio_global">
                                                  <option value="" {{ !$tipoServicioActual ? 'selected' : '' }}>-- Sin asignar --</option>
                                                  @foreach($opcionesTipoServicio ?? [] as $val => $label)
                                                      <option value="{{ $val }}" {{ $tipoServicioActual === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                  @endforeach
                                              </select>
                                          </div>
                                          <span class="secondary-content" style="line-height:2; text-align:right;">
                                              @if($cntDesayunoMenu > 0)
                                                  <span style="display:block; font-size:15px;">☕ Desayuno: <strong style="font-size:18px;">{{$cntDesayunoMenu}}</strong></span>
                                              @endif
                                              @if($cntOnceMenu > 0)
                                                  <span style="display:block; font-size:15px;">🫖 Once: <strong style="font-size:18px;">{{$cntOnceMenu}}</strong></span>
                                              @endif
                                          </span>
                                      </li>
                                  </ul>
                              </div>
                          </div>
                          @elseif($incluyeAmbos ?? false)
                          @php
                              $cntDesayunoMenu = $menus->count();
                              $cntOnceMenu     = $menus->count();
                          @endphp
                          <div class="row" style="margin-top:16px;">
                              <div class="col s12 m6 l4">
                                  <ul class="collection">
                                      <li class="collection-item avatar">
                                          <i class="material-icons circle blue">free_breakfast</i>
                                          <span class="title"><strong>Desayuno / Once</strong></span>
                                          <p>Incluidos para todos los asistentes</p>
                                          <span class="secondary-content" style="line-height:2; text-align:right;">
                                              <span style="display:block; font-size:15px;">☕ Desayuno: <strong style="font-size:18px;">{{$cntDesayunoMenu}}</strong></span>
                                              <span style="display:block; font-size:15px;">🫖 Once: <strong style="font-size:18px;">{{$cntOnceMenu}}</strong></span>
                                          </span>
                                      </li>
                                  </ul>
                              </div>
                          </div>
                          <input type="hidden" name="tipo_servicio" value="desayuno_y_once">
                          @endif

                          @endif

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
    // Inicializar selects de Materialize (incluyendo tipo_servicio)
    document.addEventListener('DOMContentLoaded', function() {
        var elems = document.querySelectorAll('select');
        M.FormSelect.init(elems);
    });

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


@endsection