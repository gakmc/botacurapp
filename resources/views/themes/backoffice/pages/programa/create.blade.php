@extends('themes.backoffice.layouts.admin')

@section('title','Crear Programa')

@section('head')
@endsection

@section('breadcrumbs')
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
  <p class="caption">Introduce los datos para crear un nuevo Programa</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
    <div class="row">
      <div class="col s12 m8 offset-m2 ">
        <div class="card-panel">
          <h4 class="header2">Crear Programa</h4>
          <div class="row">
            <form class="col s12" method="post" action="{{route('backoffice.programa.store')}}" enctype="multipart/form-data">


              {{csrf_field() }}
              <div class="row">
                <div class="input-field col s12 m6">
                  <input id="nombre_programa" type="text" name="nombre_programa">
                  <label for="nombre_programa">Nombre del Programa</label>
                  @error('nombre_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror


                </div>

                <div class="input-field col s12 m6">
                  {{-- <input id="espacio_tipo" type="text" name="espacio_tipo"> --}}
                  <select name="espacio_tipo" id="espacio_tipo">
                    <option value="" disabled selected>-- Seleccione --</option>
                    <option value="estacion_economico">Estación Económica</option>
                    <option value="estacion_intermedio">Estación Intermedia</option>
                    <option value="estacion_full">Estación Full</option>
                    <option value="terraza">Terraza</option>
                    <option value="reposera">Reposera</option>
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
                  <input id="valor_programa" type="text" class="materialize-textarea" name="valor_programa" readonly>
                  <label for="valor_programa">Valor Programa</label>

                  @error('valor_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

                <div class="input-field col s12 m4">
                  <input id="descuento" type="text" class="materialize-textarea" name="descuento">
                  <label for="descuento">Descuento</label>

                  @error('descuento')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="input-field col s12 m4">
                  <input id="min_personas" type="number" name="min_personas" value="1" min="1">
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
                    <input type="checkbox" id="permite_giftcard" name="permite_giftcard" value="1">
                    <label for="permite_giftcard" class="black-text">Se permite en Giftcard</label>
                  </p>
                </div>

                <div class="col s12 m6">
                  <p>
                    <input type="checkbox" id="solo_plataforma" name="solo_plataforma" value="1">
                    <label for="solo_plataforma" class="black-text">Solo en plataforma (no publicar en WooCommerce)</label>
                  </p>
                </div>
              </div>

              <br>


              <div class="row">
                  
                <div class="col s12 l4" style="border: 2px solid #039B7B; margin:auto">
                  @foreach($servicios as $servicio)
                    <p>
                      <input type="checkbox" id="{{$servicio->id}}" name="servicios[]" value="{{$servicio->id}}" data-valor="{{ $servicio->valor_servicio }}"/>
                      <label for="{{$servicio->id}}">
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

              <div class="row">
                <div class="col s12">
                </div>
              </div>

              <br>
              <div class="row">
                <div class="col s12">
                  <label for="imagenes" class="black-text"><span> Imágenes principales — la primera será la imagen destacada del producto. </span><small class="grey-text">Requerida para publicar en WooCommerce.</small></label>
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
                    <button class="btn waves-effect waves-light right" type="submit">Guardar
                      <i class="material-icons right">send</i>
                    </button>
                  </div>
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
          
          if (dcto !== undefined) {
            total = Math.max(total - dcto, 0);  
          }

          $('#valor_programa').val(total);
        }

      // Inicializar el total al cargar la página
      calcularTotal();

      // Recalcular el total cada vez que se selecciona o deselecciona un servicio
      $('input[name="servicios[]"]').change(function() {
          calcularTotal();
      });

      $('#descuento').change(function () { 
        calcularTotal();
      });
  });
</script>
@endsection