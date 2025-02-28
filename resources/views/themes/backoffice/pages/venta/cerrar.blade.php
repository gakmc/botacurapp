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
                  <input id="abono_programa" type="text" name="abono_programa" class="money-format"
                    value="{{'$'.number_format(old('abono_programa') ?? $reserva->venta->abono_programa, 0,'', '.')}}" readonly>
                  @error('abono_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

                <div class="file-field input-field col s12 m5">
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
                  <input id="diferencia_programa" type="text" name="diferencia_programa" class="money-format" value="{{old('diferencia_programa')}}">
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
                  <input id="sinPropina" type="text" name="sinPropina" class="money-format" value="0" readonly>
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
                  <input id="valor_consumo" type="text" name="valor_consumo" class="money-format" value="{{ old('valor_consumo') }}"
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
                  <input id="propinaValue" type="text" name="propinaValue" data-propinavalue="{{$totalSubtotal*0.1}}" class="money-format" value="{{$totalSubtotal*0.1}}">
                  @error('propinaValue')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>


                <div class="input-field col s12 m3" id="noPropina">

                  <label for="sinPropina">Consumo Sin Propina</label>
                  <input id="sinPropina" type="text" name="sinPropina" class="money-format" data-sinpropina="{{$consumo->subtotal}}" value="{{$consumo->subtotal}}" readonly>
                  @error('sinPropina')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>



                <div class="input-field col s12 m3" id="soloConsumo">

                  <label for="soloConsumo">Consumo</label>
                  <input id="soloConsumo" type="text" name="soloConsumo" class="money-format" data-sinpropina="{{$consumo->subtotal}}" value="{{$consumo->subtotal}}" readonly>
                  @error('sinPropina')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>






                <div class="input-field col s12 m3" id="siPropina" hidden>

                  <label for="conPropina">Consumo Con Propina</label>
                  <input id="conPropina" type="text" name="conPropina" class="money-format" value="{{$consumo->total_consumo}}" data-conpropina="{{$consumo->total_consumo}}"
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
                  <input id="diferencia" type="text" name="diferencia" value="{{$venta->total_pagar}}" class="money-format" data-total-pagar="{{$venta->total_pagar}}" readonly>
                  @error('diferencia')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>


                <div class="input-field col s12 m3">

                  <label for="total_pagar">Total a Pagar</label>
                  <input id="total_pagar" type="text" name="total_pagar" class="money-format" data-total-pagar="{{$reserva->venta->total_pagar}}" readonly>
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





{{-- <script>
  var total = parseInt($('#total_pagar').data('total-pagar'));  
  var propinaOriginal = parseInt($('#propinaValue').data('propinavalue'));
  var diferenciaInput = 0;
  var ConPropina = parseInt($('#conPropina').data('conpropina'));
  var SinPropina = parseInt($('#sinPropina').data('sinpropina'));
  var propinaValue = parseInt($('#propinaValue').data('propinavalue'));
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

      console.log($('#total_pagar').val(total + SinPropina));
      
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

    // Función para formatear números en CLP
    function formatCLP(number) {
      if (!number || isNaN(number)) return '$0';
      return '$' + parseInt(number, 10).toLocaleString('es-CL');
    }
</script> --}}



<script>
  // Inicialización de elementos ocultos o visibles al cargar la página
  $(document).ready(function () {
      inicializarEstado();

      // Eventos para actualización de valores y cambios
      $('#separar, #propina').on('change', actualizarValores);
      $('#propinaValue, #diferencia_programa').on('input', actualizarValores);

      $('#diferencia_programa').on('blur', function() {
          var valor = parseCurrency($(this).val()); // Convertir input a número real
          if (!isNaN(valor) && valor > 0) {
              $(this).val(formatCLP(valor)); // Aplicar formato CLP solo si es válido
          } else {
              $(this).val(''); // Si el valor es inválido, dejarlo vacío
          }
      });
  });

  function inicializarEstado() {
      // Asignar valores iniciales y establecer estado del formulario
      var total = obtenerValorData('#total_pagar', 'total-pagar');
      var SinPropina = obtenerValorData('#sinPropina', 'sinpropina');
      var ConPropina = obtenerValorData('#conPropina', 'conpropina');
      var diferencia = obtenerValorData('#diferencia', 'total-pagar');

      $('#total_pagar').val(formatCLP(total + SinPropina));
      
      
      $('#sinPropina').val(formatCLP(SinPropina));
      $('#diferencia').val(formatCLP(diferencia));

      if (ConPropina <= 0) {
          $('#check').attr('hidden', true);
      }

      cambioConsumo(false);
  }

  function actualizarValores() {
    var total = obtenerValorData('#total_pagar', 'total-pagar');
    var propina = $('#propina').is(':checked');
    var consumo = $('#separar').is(':checked');
    var SinPropina = obtenerValorData('#sinPropina', 'sinpropina');
    // var ConPropina = obtenerValorData('#conPropina', 'conpropina');
    var diferenciaInput = parseCurrency($('#diferencia_programa').val());
    var propinaInput = parseCurrency($('#propinaValue').val());

    var nuevoTotal = total;

    var valorConsumo = 0;
    var ConPropina = SinPropina + propinaInput;

    if (consumo) {
        cambioConsumo(true);

        // Mostrar el valor del consumo según el estado de la propina
        valorConsumo = propina ? ConPropina : SinPropina;
        $('#valor_consumo').val(formatCLP(valorConsumo));

        // Restar el consumo del total
        // nuevoTotal -= valorConsumo;
    } else {
        cambioConsumo(false);
        $('#valor_consumo').val('');
        valorConsumo = propina ? ConPropina : SinPropina;
        // $('#total_pagar').val(formatCLP(nuevoTotal+valorConsumo))
    }

    if (!consumo) {
      nuevoTotal += valorConsumo;
    }

    // Manejo de la propina
    if (propina) {
        manejarPropina(true, ConPropina);
        
    } else {
        manejarPropina(false, SinPropina);
    }

    // Aplicar diferencia
    nuevoTotal -= diferenciaInput;

    // Actualizar el valor total a pagar
    $('#total_pagar').val(formatCLP(nuevoTotal));
    $('#conPropina').val(formatCLP(ConPropina));
  }


  function manejarPropina(activar, ConPropina) {
      if (activar) {
          // Ocultar opciones sin propina y habilitar opciones con propina
          $('#noPropina').attr('hidden', true);
          $('#sinPropina').attr('disabled', true);
          $('#siPropina, #propinaBruta, #propinaValue').removeAttr('hidden disabled');
          $('#conPropina').removeAttr('disabled');

        // Obtener el nuevo valor de propina desde el input
        let rawPropinaValue = $('#propinaValue').val();

        let nuevaPropinaValue = rawPropinaValue;

        if (!isNaN(nuevaPropinaValue)) {
            // Actualizar el total con la nueva propina
            ConPropina = ConPropina - obtenerValorData('#propinaValue', 'propinavalue');
            console.log( typeof(ConPropina),typeof(nuevaPropinaValue));
            nuevaPropinaValue = parseCurrency(nuevaPropinaValue);
            ConPropina += nuevaPropinaValue;


            // Actualizar el valor almacenado en data y el campo total
            $('#propinaValue').val(formatCLP(nuevaPropinaValue));
            $('#conPropina').val(formatCLP(ConPropina));
        }
      } else {
          // Ocultar y deshabilitar los controles relacionados con la propina
          $('#siPropina').attr('hidden', true);
          $('#conPropina').attr('disabled', true);
          $('#noPropina').removeAttr('hidden');
          $('#sinPropina').removeAttr('disabled');
          $('#propinaValue').attr('disabled', true);
          $('#propinaBruta').attr('hidden', true);
      }
  }


  function cambioConsumo(enable) {
      if (enable) {
          $('#div_valor_consumo, #div_imagen_consumo, #div_id_tipo_transaccion').removeAttr('hidden');
          $('#valor_consumo, #imagen_consumo, #id_tipo_transaccion').removeAttr('disabled');
          
      } else {
          $('#div_valor_consumo, #div_imagen_consumo, #div_id_tipo_transaccion').attr('hidden', true);
          $('#valor_consumo, #imagen_consumo, #id_tipo_transaccion').attr('disabled', true);
      }
  }

  // Funciones auxiliares
  function obtenerValorData(selector, dataAttr) {
      return parseInt($(selector).data(dataAttr)) || 0;
  }

  function parseCurrency(value) {
      // // return Number(value.replace(/[^0-9.-]+/g, "")) || 0;
      // return Number(value.replace(/[^0-9.-]/g, "")) || 0;
      if (!value) return 0; // Si el valor está vacío, devolver 0
      return parseInt(value.replace(/[^0-9]/g, ""), 10) || 0;
  }

  function formatCLP(number) {
      return isNaN(number) ? '$0' : '$' + parseInt(number, 10).toLocaleString('es-CL');
  }
</script>



@endsection