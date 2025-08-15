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
                          <div class="row">
                              <h6><strong> Menús por asistente</strong></h6>

                              {{-- @for ($i = 1; $i <= $reserva->cantidad_personas; $i++) --}}

                              @foreach ($menus as $menu)

                                  <div class="input-field col s12 m6 l3">
                                      <select name="menus[{{ $menu->id }}][id_producto_entrada]"
                                          id="id_producto_entrada_{{ $menu->id }}">
                                          <option value="" disabled selected> -- Seleccione --</option>
                                          @foreach ($entradas as $entrada)
                                          <option value="{{$entrada->id}}" {{ $menu->id_producto_entrada === $entrada->id ? 'selected'
                                          : '' }}>{{$entrada->nombre}}</option>
                                          @endforeach
                                      </select>
                                      {{-- @error('id_producto_entrada_{{ $menu->id }}') --}}
                                      @error('menus.'.$menu->id.'.id_producto_entrada')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                      <label for="id_producto_entrada_{{ $menu->id }}">Entrada</label>
                                  </div>



                                  <div class="input-field col s12 m6 l2">
                                      <select name="menus[{{$menu->id}}][id_producto_fondo]" id="id_producto_fondo_{{$menu->id}}">
                                          <option value="" disabled selected> -- Seleccione --</option>
                                          @foreach ($fondos as $fondo)
                                          <option value="{{$fondo->id}}" {{ $menu->id_producto_fondo === $fondo->id ? 'selected'
                                          : '' }}>{{$fondo->nombre}}</option>
                                          @endforeach
                                      </select>
                                      {{-- @error('id_producto_fondo_{{$menu->id}}')
                                       --}}
                                       @error('menus.'.$menu->id.'.id_producto_fondo')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                      <label for="id_producto_fondo_{{ $menu->id }}">Fondo</label>
                                  </div>


                                  <div class="input-field col s12 m6 l2">
                                      <select name="menus[{{$menu->id}}][id_producto_acompanamiento]"
                                          id="id_producto_acompanamiento_{{$menu->id}}">
                                          <option value="" disabled selected> -- Seleccione --</option>
                                          <option value="" {{ $menu->id_producto_acompanamiento === null ? 'selected'
                                          : '' }}>Sin Acompañamiento</option>
                                          @foreach ($acompañamientos as $acompañamiento)
                                          <option value="{{$acompañamiento->id}}" {{ $menu->id_producto_acompanamiento === $acompañamiento->id ? 'selected'
                                          : '' }}>{{$acompañamiento->nombre}}</option>
                                          @endforeach
                                      </select>
                                      {{-- @error('menus.'.$menu->id.'.id_producto_acompanamiento') --}}
                                      @error('menus.'.$menu->id.'.id_producto_acompanamiento')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                      <label for="id_producto_acompanamiento_{{ $menu->id }}">Acompañamiento</label>
                                  </div>

                                  <div class="input-field col s12 m6 l2">

                                      <input id="alergias_{{$menu->id}}" type="text" name="menus[{{ $menu->id }}][alergias]"
                                          class="" value="{{ $menu->alergias }}">
                                      <label for="alergias_{{$menu->id}}">Alérgias</label>
                                      {{-- @error('alergias_{{$menu->id}}') --}}
                                      @error('menus.'.$menu->id.'.alergias')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                  </div>


                                  <div class="input-field col s12 m6 l2">
                                      <input type="text" name="menus[{{ $menu->id }}][observacion]"
                                          id="observacion_{{ $menu->id }}" value="{{ $menu->observacion }}"/>
                                      <label for="observacion_{{$menu->id}}">Observaciones</label>
                                      {{-- @error('observacion_{{ $menu->id }}') --}}
                                      @error('menus.'.$menu->id.'.observacion')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                      @enderror
                                  </div>

                                  @endforeach
                                  {{-- @endfor --}}

                                  @endif
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