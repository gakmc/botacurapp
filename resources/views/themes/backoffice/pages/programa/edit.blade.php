@extends('themes.backoffice.layouts.admin')

@section('title','Editar Programa ' . $programa->nombre_programa)
@section('head')
@endsection

@section('breadcrumbs')
@endsection

@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
  <p class="caption">Introduce los datos para editar un Programa</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
    <div class="row">
      <div class="col s12 m8 offset-m2">
        <div class="card-panel">
          <h4 class="header2">Editar Programa</h4>
          <div class="row">
            <form class="col s12" method="post" action="{{route('backoffice.programa.update', $programa)}}" enctype="multipart/form-data">

              {{csrf_field()}}
              {{method_field('PUT')}}

              <div class="row">
                <div class="input-field col s12 m6">
                  <input id="nombre_programa" type="text" name="nombre_programa" value="{{$programa->nombre_programa}}">
                  <label for="nombre_programa" class="active">Nombre del Programa</label>
                  @error('nombre_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="input-field col s12 m6">
                  <select name="espacio_tipo" id="espacio_tipo">
                    <option value="" disabled {{ $programa->espacio_tipo ? '' : 'selected' }}>-- Seleccione --</option>
                    <option value="estacion_economico"  {{ $programa->espacio_tipo === 'estacion_economico'  ? 'selected' : '' }}>Estación Económica</option>
                    <option value="estacion_intermedio" {{ $programa->espacio_tipo === 'estacion_intermedio' ? 'selected' : '' }}>Estación Intermedia</option>
                    <option value="estacion_full"       {{ $programa->espacio_tipo === 'estacion_full'       ? 'selected' : '' }}>Estación Full</option>
                    <option value="terraza"             {{ $programa->espacio_tipo === 'terraza'             ? 'selected' : '' }}>Terraza</option>
                    <option value="reposera"            {{ $programa->espacio_tipo === 'reposera'            ? 'selected' : '' }}>Reposera</option>
                  </select>
                  <label for="espacio_tipo">Tipo de espacio</label>
                  @error('espacio_tipo')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>
              </div>

              <div class="row">
                <div class="input-field col s12 m4">
                  <input id="valor_programa" type="text" name="valor_programa" value="{{$programa->valor_programa}}" readonly>
                  <label for="valor_programa" class="active">Valor Programa</label>
                  @error('valor_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="input-field col s12 m4">
                  <input id="descuento" type="text" name="descuento" value="{{$programa->descuento}}">
                  <label for="descuento" class="active">Descuento</label>
                  @error('descuento')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="input-field col s12 m4">
                  <input id="min_personas" type="number" name="min_personas" value="{{ $programa->min_personas ?? 1 }}" min="1">
                  <label for="min_personas" class="active">Mín. personas</label>
                  @error('min_personas')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>
              </div>

              <div class="row">
                <div class="col s12 m6">
                  <p>
                    <input type="checkbox" id="permite_giftcard" name="permite_giftcard" value="1"
                      @if($programa->permite_giftcard) checked @endif>
                    <label for="permite_giftcard" class="black-text">Se permite en Giftcard</label>
                  </p>
                </div>

                <div class="col s12 m6">
                  <p>
                    <input type="checkbox" id="solo_plataforma" name="solo_plataforma" value="1"
                      @if($programa->solo_plataforma) checked @endif>
                    <label for="solo_plataforma" class="black-text">Solo en plataforma (no sincronizar con WooCommerce)</label>
                  </p>
                </div>
              </div>

              <br>

              <div class="row">
                <div class="col s12 m6 l4" style="border: 2px solid #039B7B;">
                  <label class="black-text">Servicios</label>
                  @foreach($servicios as $servicio)
                    <p>
                      <input type="checkbox" id="edit_srv_{{$servicio->id}}" name="servicios[]"
                        value="{{$servicio->id}}" data-valor="{{ $servicio->valor_servicio }}"
                        @if($programa->servicios->contains($servicio->id)) checked @endif />
                      <label for="edit_srv_{{$servicio->id}}">
                        <span class="black-text">{{$servicio->nombre_servicio}}</span>
                      </label>
                    </p>
                  @endforeach
                  @error('servicios')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>
              </div>

              <br>

              <div class="row">
                <div class="col s12">
                  <label for="imagenes" class="black-text">
                    <span>Nueva imagen principal</span>
                    @if($programa->wc_main_image_ids)
                      <small class="grey-text"> — ya tiene {{ count($programa->wc_main_image_ids) }} imagen(es); subir reemplazará las actuales</small>
                    @endif
                    <small class="grey-text"> Requerida para publicar en WooCommerce.</small>
                  </label>
                </div>
                <div class="col s12">
                  <input type="file" id="imagenes" name="imagenes[]" accept="image/jpeg,image/jpg,image/png,image/webp" multiple>
                  @error('imagenes')
                  <span style="color:red"><strong>{{ $message }}</strong></span>
                  @enderror
                  @error('imagenes.*')
                  <span style="color:red"><strong>{{ $message }}</strong></span>
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
  $(document).ready(function(){
    $('select').formSelect();

    function calcularTotal() {
      let total = 0;
      let dcto = parseInt($('#descuento').val()) || 0;

      $('input[name="servicios[]"]:checked').each(function() {
        total += $(this).data('valor');
      });

      total = Math.max(total - dcto, 0);
      $('#valor_programa').val(total);
    }

    calcularTotal();

    $('input[name="servicios[]"]').change(function() {
      calcularTotal();
    });

    $('#descuento').change(function() {
      calcularTotal();
    });
  });
</script>
@endsection
