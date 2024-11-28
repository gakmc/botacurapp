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
              action="{{route('backoffice.reserva.venta.cerrarventa', ['reserva' => $reserva->id, 'ventum' => $venta])}}">


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
                    <input type="file" id="imagen_abono" name="imagen_abono" value="{{$reserva->venta->imagen_abono}}"
                      disabled>
                  </div> --}}
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="{{$reserva->venta->imagen_abono}}"
                      value="{{$reserva->venta->imagen_abono}}" readonly>
                  </div>
                  @error('imagen_abono')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>


                <div class="input-field col s12 m4">
                  @if (is_null($reserva->venta->tipoTransaccionAbono))
                  <select name="id_tipo_transaccion_abono" id="id_tipo_transaccion_abono">

                    <option value="{{$reserva->venta->id_tipo_transaccion_abono}}" selected>
                      No Registrada</option>
                    @else
                    <select name="id_tipo_transaccion_abono" id="id_tipo_transaccion_abono" disabled>
                      <option value="{{$reserva->venta->id_tipo_transaccion_abono}}" disabled selected>
                        {{$reserva->venta->tipoTransaccionAbono->nombre}}</option>

                      @endif
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



                @if ($reserva->venta->consumos->isEmpty())

                <div class="input-field col s12 m3" id="noPropina">

                  <label for="sinPropina">Consumo Sin Propina</label>
                  <input id="sinPropina" type="text" name="sinPropina" class="" value="0" readonly>
                  @error('sinPropina')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>


                @else





                <p>
                  <label>
                    <input type="checkbox" id="separar" name="separar" />
                    <span class="black-text">Separar Consumo?</span>
                  </label>
                </p>


                <div class="input-field col s12 m3" id="div_valor_consumo">

                  <label for="valor_consumo">Valor Consumo</label>
                  <input id="valor_consumo" type="text" name="valor_consumo" class="" value="{{ old('valor_consumo') }}"
                    readonly>
                  @error('valor_consumo')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

                <div class="file-field input-field col s12 m5" id="div_imagen_consumo">
                  <div class="btn">
                    <span>Imagen Pago Consumo</span>
                    <input type="file" id="imagen_consumo" name="imagen_consumo">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Seleccione su archivo">
                  </div>
                  @error('imagen_consumo')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>


                <div class="input-field col s12 m4" id="div_id_tipo_transaccion">
                  <select name="id_tipo_transaccion" id="id_tipo_transaccion">
                    <option selected disabled>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                    <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  @error('id_tipo_transaccion')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                  <label for="id_tipo_transaccion">Tipo Transaccion Consumo</label>

                </div>




                @foreach ($reserva->venta->consumos as $consumo)
                <p id="check">
                  <label>
                    <input type="checkbox" id="propina" name="propina" />
                    <span class="black-text">Incluye Propina?</span>
                  </label>
                </p>

                @php
                  $totalSubtotal = $consumo->detallesConsumos->where('id_consumo', $consumo->id)->sum('subtotal');
                @endphp

                <div class="input-field col s12 m3" id="propinaBruta" hidden>

                  <label for="propinaValue">ingrese Propina</label>
                  <input id="propinaValue" type="text" name="propinaValue" class="" value="{{$totalSubtotal*0.1}}">
                  @error('propinaValue')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>


                <div class="input-field col s12 m3" id="noPropina">

                  <label for="sinPropina">Consumo Sin Propina</label>
                  <input id="sinPropina" type="text" name="sinPropina" class="" value="{{$consumo->subtotal}}" readonly>
                  @error('sinPropina')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>






                <div class="input-field col s12 m3" id="siPropina" hidden>

                  <label for="conPropina">Consumo Con Propina</label>
                  <input id="conPropina" type="text" name="conPropina" class="" value="{{$consumo->total_consumo}}"
                    readonly>
                  @error('conPropina')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>


                @endforeach
                @endif

                <div class="input-field col s12 m3">

                  <label for="diferencia">Diferencia por Pagar</label>
                  <input id="diferencia" type="text" name="diferencia" value="{{$venta->total_pagar}}" class="" readonly
                    data-total-pagar="{{$venta->total_pagar}}">
                  @error('diferencia')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>


                <div class="input-field col s12 m3">

                  <label for="total_pagar">Total a Pagar</label>
                  <input id="total_pagar" type="text" name="total_pagar" class="" readonly
                    data-total-pagar="{{$reserva->venta->total_pagar}}">
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
                  <img class="center-text" id="imagenSeleccionadaAbono"
                    src="{{$reserva->venta->imagen_abono ? route('backoffice.reserva.abono.imagen', $reserva->id) : '/images/gallary/no-image.png'}}"
                    alt="" style="max-height: 200px; max-width:300px;">
                </div>

                <div class="input-field col s12 m6">
                  <label for="imagenSeleccionadaDiferencia">Imagen Diferencia</label>
                  <img class="center-text" id="imagenSeleccionadaDiferencia" src="/images/gallary/no-image.png" alt=""
                    style="max-height: 200px; max-width:300px;">
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
  var total = parseInt($('#total_pagar').data('total-pagar'));
    var propinaOriginal = parseInt($('#propinaValue').val());
    var diferenciaInput = 0;
    var ConPropina = parseInt($('#conPropina').val());
    var SinPropina = parseInt($('#sinPropina').val());
    var propinaValue = parseInt($('#propinaValue').val());
    var propina = false;
    var consumo = false;
    var nuevoTotal = 0;
    var checkboxPropina = $('#check');
    var checkboxConsumo = $('#separar');
    var divValorConsumo = $('#div_valor_consumo');
    var divImagenConsumo = $('#div_imagen_consumo');
    var divTransaccionConsumo = $('#div_id_tipo_transaccion');

    var valorConsumo = $('#valor_consumo');
    var imagenConsumo = $('#imagen_consumo');
    var transaccionConsumo = $('#id_tipo_transaccion');

    $(document).ready(function () {
        $('#total_pagar').val(total + SinPropina);

        if (ConPropina <= 0) {
            checkboxPropina.attr('hidden', true);
        }

        // Inicializa el estado de los campos relacionados con consumo
        cambioConsumo(false);
    });

    $(document).change(function () {
        nuevoTotal = total;
        propina = $('#propina').is(':checked');
        consumo = checkboxConsumo.is(':checked');

        if (consumo) {
            cambioConsumo(true);

            if (propina) {
                valorConsumo.val($('#conPropina').val());
            } else {
                valorConsumo.val($('#sinPropina').val());
            }

            // Resta el valor del consumo del total
            nuevoTotal -= parseInt(valorConsumo.val() || 0);
        } else {
            cambioConsumo(false);
            valorConsumo.val('');
        }

        if (propina) {
            $('#noPropina').attr('hidden', true);
            $('#sinPropina').attr('disabled', true);
            $('#siPropina').removeAttr('hidden');
            $('#conPropina').removeAttr('disabled');
            $('#propinaBruta').removeAttr('hidden');
            $('#propinaValue').removeAttr('disabled');

            var nuevaPropinaValue = parseInt($('#propinaValue').val());
            if (!isNaN(nuevaPropinaValue)) {
                ConPropina = ConPropina - propinaValue + nuevaPropinaValue;
                propinaValue = nuevaPropinaValue;
                $('#conPropina').val(ConPropina);
            }

            nuevoTotal += ConPropina;
        } else {
            $('#siPropina').attr('hidden', true);
            $('#conPropina').attr('disabled', true);
            $('#noPropina').removeAttr('hidden');
            $('#sinPropina').removeAttr('disabled');
            $('#propinaValue').val(propinaOriginal);
            $('#propinaBruta').attr('hidden', true);
            $('#propinaValue').attr('disabled', true);
            nuevoTotal += SinPropina;
        }

        calcularTotal();
    });

    $('#diferencia_programa').change(function () {
        diferenciaInput = $('#diferencia_programa').val();
        calcularTotal();
    });

    function calcularTotal() {
        $('#total_pagar').val(nuevoTotal - diferenciaInput);
    }

    function cambioConsumo(enable) {
        if (enable) {
            divValorConsumo.removeAttr('hidden');
            divImagenConsumo.removeAttr('hidden');
            divTransaccionConsumo.removeAttr('hidden');
            valorConsumo.removeAttr('disabled');
            imagenConsumo.removeAttr('disabled');
            transaccionConsumo.removeAttr('disabled');
        } else {
            divValorConsumo.attr('hidden', true);
            divImagenConsumo.attr('hidden', true);
            divTransaccionConsumo.attr('hidden', true);
            valorConsumo.attr('disabled', true);
            imagenConsumo.attr('disabled', true);
            transaccionConsumo.attr('disabled', true);
        }
    }
</script>

@endsection