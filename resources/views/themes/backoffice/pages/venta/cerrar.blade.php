@extends('themes.backoffice.layouts.admin')

@section('title','Generar Venta')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $reserva) }}">Ventas asociadas a la reserva del cliente</a></li>
<li>Crear Venta</li>
{{-- {{ dd($reserva, $venta, $tipos) }} --}}

@endsection



@section('content')

<div class="section">
  <p class="caption">Introduce los datos para cerrar la venta</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
    <div class="row">
      <div class="col s12 m8 offset-m2 ">
        <div class="card-panel">
          <h4 class="header">Cerrar Venta para la reserva de <strong>{{$reserva->cliente->nombre_cliente}}</strong>
          </h4>
          <div class="row">
            <form class="col s12" method="post" enctype="multipart/form-data"
              action="{{route('backoffice.reserva.venta.update', ['reserva' => $reserva->id, 'ventum' => $reserva->venta->id])}}">


              {{csrf_field() }}
              {{method_field('PUT')}}



              <div class="row">
                <div class="input-field col s12" type="hidden">
                  <input id="id_reserva" type="hidden" class="form-control" name="id_reserva" value="{{$reserva->id}}"
                    required>
                </div>


                <div class="input-field col s12 m3">

                  <label for="abono_programa">Cantidad de Abono</label>
                  <input id="abono_programa" type="text" name="abono_programa" class=""
                    value="{{ old('abono_programa') ?? $reserva->venta->abono_programa}}" readonly>
                  @error('abono_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

                <div class="file-field input-field col s12 m5">
                  {{-- <div class="btn">
                    <span>Imagen Abono</span>
                    <input type="file" id="imagen_abono" name="imagen_abono" value="{{$reserva->venta->imagen_abono}}" disabled>
                  </div> --}}
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="{{$reserva->venta->imagen_abono}}" value="{{$reserva->venta->imagen_abono}}" readonly>
                  </div>
                  @error('imagen_abono')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>


                <div class="input-field col s12 m4">
                  <select name="id_tipo_transaccion_abono" id="id_tipo_transaccion_abono" disabled>
                    <option value="{{$reserva->venta->id_tipo_transaccion_abono}}" disabled selected>
                      {{$reserva->venta->tipoTransaccionAbono->nombre}}</option>
                    @foreach ($tipos as $tipo)
                    <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  <label for="id_tipo_transaccion_abono">Tipo Transaccion Abono</label>
                </div>



              </div>


              <div class="row">

                <div class="input-field col s12 m3">

                  <label for="diferencia_programa">Cantidad de diferencia</label>
                  <input id="diferencia_programa" type="text" name="diferencia_programa" class=""
                    value="{{ old('diferencia_programa') }}">
                  @error('diferencia_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

                <div class="file-field input-field col s12 m5">
                  <div class="btn">
                    <span>Imagen Diferencia</span>
                    <input type="file" id="imagen_diferencia" name="imagen_diferencia">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Seleccione su archivo">
                  </div>
                  @error('imagen_diferencia')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>


                <div class="input-field col s12 m4">
                  <select name="id_tipo_transaccion_diferencia" id="id_tipo_transaccion_diferencia">
                    <option selected disabled>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                    <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  @error('id_tipo_transaccion_diferencia')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                  <label for="id_tipo_transaccion_diferencia">Tipo Transaccion Diferencia</label>

                </div>


              </div>

              <div class="row">

                {{-- <div class="input-field col s12 m3">

                  <label for="descuento">Descuento</label>
                  <input id="descuento" type="number" name="descuento" class="" value="{{ old('descuento') }}">
                  @error('descuento')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div> --}}

                <div class="input-field col s12 m3">

                  <label for="total_pagar">Total a pagar</label>
                  <input id="total_pagar" type="text" name="total_pagar" class=""
                    value="{{$reserva->venta->total_pagar}}" readonly>
                  @error('total_pagar')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

              </div>


              <div class="row">
                <div class="input-field col s12 m6">
                  <label for="imagenSeleccionadaAbono">Imagen Abono</label>
                  <img class="center-text" id="imagenSeleccionadaAbono" src="{{$reserva->venta->imagen_abono ? route('backoffice.reserva.abono.imagen', $reserva->id) : '/images/gallary/no-image.png'}}" alt=""
                    style="max-height: 200px">
                </div>

                <div class="input-field col s12 m6">
                  <label for="imagenSeleccionadaDiferencia">Imagen Diferencia</label>
                  <img class="center-text" id="imagenSeleccionadaDiferencia" src="/images/gallary/no-image.png"
                    alt="" style="max-height: 200px">
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
<script>
  $(document).ready(function (e) {   
  $('#imagen_abono').change(function(){            
      let reader = new FileReader();
      reader.onload = (e) => { 
          $('#imagenSeleccionadaAbono').attr('src', e.target.result); 
      }
      reader.readAsDataURL(this.files[0]);
  });
});

  $(document).ready(function (e) {   
  $('#imagen_diferencia').change(function(){            
      let reader = new FileReader();
      reader.onload = (e) => { 
          $('#imagenSeleccionadaDiferencia').attr('src', e.target.result); 
      }
      reader.readAsDataURL(this.files[0]);
  });
});
</script>

<script>
  var total = 0;
    var diferenciaInput = 0;
    
    $(document).ready(function(){
      total = $('#total_pagar').val();
      calcularTotal();
    })

    $('#diferencia_programa').change(function () { 
      diferenciaInput = $('#diferencia_programa').val();
      calcularTotal();
     })
    

    function calcularTotal() {
      
      $('#total_pagar').val(total-diferenciaInput);
      
    }

</script>
@endsection