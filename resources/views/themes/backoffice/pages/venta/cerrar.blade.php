@extends('themes.backoffice.layouts.admin')

@section('title','Generar Venta')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $reserva) }}">Ventas asociadas a la reserva del cliente</a></li>
<li>Cerrar Venta</li>
@endsection

@section('content')
<div class="section">
  <p class="caption">Introduce los datos para cerrar la venta</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
    <div class="row">
      <div class="col s12 m10 offset-m1 ">
        <div class="card-panel">
          <h4 class="header">Cerrar Venta para la reserva de <strong>{{$reserva->cliente->nombre_cliente}}</strong></h4>
          <div class="row">
            <form class="col s12" method="post" enctype="multipart/form-data" action="{{route('backoffice.reserva.venta.cerrarventa', ['reserva' => $reserva->id, 'ventum' => $venta])}}">
              {{csrf_field() }}
              {{method_field('PUT')}}
              <input id="id_reserva" type="hidden" name="id_reserva" value="{{$reserva->id}}" required>

              @if (!empty($reserva->venta->consumo))
                @php
                  $totalSubtotal = $reserva->venta->consumo->detallesConsumos->sum('subtotal');
                  $subtotalServicios = $reserva->venta->consumo->detalleServiciosExtra->sum('subtotal');
                @endphp

              <div class="row">
                <div class="col s12">
                  <p>
                    <label>
                      <input type="checkbox" id="propina" name="propina" />
                      <span class="black-text">¿Incluir propina?</span>
                    </label>
                  </p>
                </div>
              </div>

              <div class="row">
                <br>
              </div>

                <div class="row" id="seccionPropina">
                  <div class="input-field col s12 m4">
                    <label for="consumo_bruto">Consumo</label>
                    <input id="consumo_bruto" type="text" name="consumo_bruto" class="money-format" data-consumo_bruto="{{$totalSubtotal}}" value="${{number_format($totalSubtotal,0,'','.')}}" readonly>
                  </div>

                  <div class="input-field col s12 m4" id="propinaBruta" hidden>
                    <label for="propinaValue">Ingrese Propina</label>
                    <input id="propinaValue" type="text" name="propinaValue" class="money-format" data-propinavalue="{{$totalSubtotal*0.1}}" value="${{number_format($totalSubtotal*0.1,0,'','.')}}">
                  </div>

                  <div class="input-field col s12 m4" id="siPropina" hidden>
                    <label for="conPropina">Consumo con Propina</label>
                    <input id="conPropina" type="text" name="conPropina" class="money-format" data-conpropina="{{$totalSubtotal*1.1}}" value="${{number_format($totalSubtotal*1.1,0,'','.')}}" readonly>
                  </div>
                </div>

                <div class="row">
                  <div class="input-field col s12 m4">
                    <label for="servicio_bruto">Servicios</label>
                    <input id="servicio_bruto" type="text" name="servicio_bruto" class="money-format" value="${{number_format($subtotalServicios,0,'','.')}}" data-servicio_bruto="{{$subtotalServicios}}" readonly>
                  </div>
                </div>


                <div class="row">
               
                  <div class="input-field col s12 m4">
                    <label for="sinPropina">Servicios + Consumo</label>
                    <input id="sinPropina" type="text" name="sinPropina" class="money-format" value="${{number_format($subtotalServicios + $totalSubtotal,0,'','.')}}" data-sinpropina="{{$subtotalServicios + $totalSubtotal}}" readonly>
                  </div>
                </div>

                {{-- <div class="row">

                
                  <div class="col s12">
                    <p>
                      <label>
                        <input type="checkbox" id="dividir_pago" name="dividir_pago" />
                        <span class="black-text">¿Dividir consumo entre dos personas?</span>
                      </label>
                    </p>
                  </div>
                </div>

                <div class="row">
                  <br><br>
                </div> --}}

                {{-- Sección duplicada que debe estar oculta inicialmente --}}
              {{-- <div id="duplicar_pago" class="row" hidden>
                <div class="input-field col s12 m4">
                  <label for="valor_consumo1">Valor Consumo 1</label>
                  <input id="valor_consumo1" type="text" name="pago1" class="money-format" readonly>
                </div>

          
                <div class="file-field input-field col s12 m4">
                  <div class="btn"><span>Imagen Pago 1</span>
                    <input type="file" name="imagen_pago1">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                  </div>
                </div>
                

                <div class="input-field col s12 m4">
                  <select name="id_tipo_transaccion1">
                    <option selected disabled>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                      <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  <label for="id_tipo_transaccion1">Tipo Transacción 1</label>
                </div>



                <div class="input-field col s12 m4">
                  <label for="valor_consumo2">Valor Consumo 2</label>
                  <input id="valor_consumo2" type="text" name="pago2" class="money-format">
                </div>

                <div class="file-field input-field col s12 m4">
                  <div class="btn"><span>Imagen Pago 2</span>
                    <input type="file" name="imagen_pago2">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                  </div>
                </div>

                <div class="input-field col s12 m4">
                  <select name="id_tipo_transaccion2">
                    <option selected disabled>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                      <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  <label for="id_tipo_transaccion2">Tipo Transacción 2</label>
                </div>


              </div> --}}



              @endif

              <div class="row">
                <div class="input-field col s12 m4">
                  <label for="diferencia">Diferencia por Pagar</label>
                  <input id="diferencia" type="text" name="diferencia" class="money-format" value="${{number_format($venta->total_pagar,0,'','.')}}" data-total-pagar="{{$venta->total_pagar}}" readonly>
                </div>
              </div>

              <div class="row">

                <div class="input-field col s12 m4">
                  <label for="total_pagar">Total a Pagar</label>
                  <input id="total_pagar" type="text" name="total_pagar" class="money-format" readonly>
                </div>

                <div class="file-field input-field col s12 m4">
                  <div class="btn">
                    <span>Imagen Diferencia</span>
                    <input type="file" name="imagen_diferencia">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Seleccione su archivo">
                  </div>
                </div>

                <div class="input-field col s12 m4">
                  <select name="id_tipo_transaccion_diferencia" id="id_tipo_transaccion_diferencia">
                    <option selected disabled>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                      <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  <label for="id_tipo_transaccion_diferencia">Tipo Transacción Diferencia</label>
                </div>
              </div>





              {{-- Checkbox para activar la división del pago --}}
              <div class="row">
                <div class="col s12">
                  <p>
                    <label>
                      <input type="checkbox" id="dividir_pago" name="dividir_pago" />
                      <span class="black-text">¿Dividir Pago?</span>
                    </label>
                  </p>
                </div>
              </div>

              {{-- Campos para pago dividido (ocultos inicialmente) --}}
              <div id="duplicar_pago" class="row" hidden>
                {{-- Valor a Pagar 1 --}}
                <div class="input-field col s12 m4">
                  <label for="valor_consumo1">Valor a Pagar 1</label>
                  <input id="valor_consumo1" placeholder=" " type="text" name="pago1" class="money-format" readonly>
                </div>

                <div class="file-field input-field col s12 m4">
                  <div class="btn">
                    <span>Imagen Diferencia</span>
                    <input type="file" name="imagen_diferencia_dividida">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Seleccione su archivo">
                  </div>
                </div>

                <div class="input-field col s12 m4">
                  <select name="id_tipo_transaccion_diferencia_dividida" id="id_tipo_transaccion_diferencia_dividida">
                    <option selected disabled>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                      <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  <label for="id_tipo_transaccion_diferencia_dividida">Tipo Transacción Diferencia</label>
                </div>



                {{-- Valor a Pagar 2 --}}
                <div class="input-field col s12 m4">
                  <label for="valor_consumo2">Valor a Pagar 2</label>
                  <input id="valor_consumo2" placeholder=" " type="text" name="pago2" class="money-format">
                </div>

                {{-- Imagen de pago 2 --}}
                <div class="file-field input-field col s12 m4">
                  <div class="btn"><span>Imagen Pago 2</span>
                    <input type="file" name="imagen_pago2">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                  </div>
                </div>

                {{-- Tipo de Transacción 2 --}}
                <div class="input-field col s12 m4">
                  <select name="id_tipo_transaccion2">
                    <option selected disabled>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                      <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
                    @endforeach
                  </select>
                  <label for="id_tipo_transaccion2">Tipo Transacción 2</label>
                </div>


              </div>







              <div class="row">
                <div class="input-field col s12">
                  <button id="btn-guardar" class="btn waves-effect waves-light right" type="submit">Guardar
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
</script> --}}

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                text: '{{ session('error') }}',
                confirmButtonText: 'Entendido'
            });
        });
    </script>
@endif


<script>
  function parseCurrency(value) {
      if (!value) return 0;
      return parseInt(value.replace(/[^0-9]/g, ""), 10) || 0;
  }

  function formatCLP(number) {
      return isNaN(number) ? '$0' : '$' + parseInt(number, 10).toLocaleString('es-CL');
  }

  function obtenerValorData(selector, dataAttr) {
      return parseInt($(selector).data(dataAttr)) || 0;
  }

  function recalcularTotalPagar() {
    const consumo = $('#propina').is(':checked')
    ? parseCurrency($('#conPropina').val())
    : parseCurrency($('#consumo_bruto').val());

    const servicio = parseCurrency($('#servicio_bruto').val());
    const diferencia = parseCurrency($('#diferencia').val());

    const total = consumo + servicio + diferencia;


      $('#total_pagar').val(formatCLP(total));
  }

  function manejarPropina(activar, ConPropina) {
      if (activar) {
          $('#siPropina, #propinaBruta, #propinaValue').removeAttr('hidden disabled');
          $('#conPropina').removeAttr('disabled');

          let rawPropinaValue = $('#propinaValue').val();
          let nuevaPropinaValue = rawPropinaValue;

          if (!isNaN(nuevaPropinaValue)) {
              ConPropina = ConPropina - obtenerValorData('#propinaValue', 'propinavalue');
              nuevaPropinaValue = parseCurrency(nuevaPropinaValue);
              ConPropina += nuevaPropinaValue;

              $('#propinaValue').val(formatCLP(nuevaPropinaValue));
              $('#conPropina').val(formatCLP(ConPropina));
          }
      } else {
          $('#siPropina').attr('hidden', true);
          $('#conPropina').attr('disabled', true);
          $('#noPropina').removeAttr('hidden');
          $('#sinPropina').removeAttr('disabled');
          $('#propinaValue').attr('disabled', true);
          $('#propinaBruta').attr('hidden', true);
      }
  }

  function actualizarValores() {
    const propinaActiva = $('#propina').is(':checked');
    const consumoBruto = obtenerValorData('#consumo_bruto', 'consumo_bruto');
    const propinaInput = parseCurrency($('#propinaValue').val());
    const servicios = obtenerValorData('#servicio_bruto', 'servicio_bruto');

    let consumoConPropina = consumoBruto + (propinaActiva ? propinaInput : 0);
    // let totalServiciosConsumo = servicios + consumoConPropina;

    let consumoFinal = propinaActiva ? consumoConPropina : consumoBruto;
    let totalServiciosConsumo = servicios + consumoFinal;

    // Actualizar los campos visibles
    $('#conPropina').val(formatCLP(consumoConPropina));
    // $('#sinPropina').val(formatCLP(totalServiciosConsumo));
    $('#sinPropina').val(formatCLP(totalServiciosConsumo));

    
    // Mostrar u ocultar propina según el estado del checkbox
    if (propinaActiva) {
        $('#propinaBruta').removeAttr('hidden');
        $('#siPropina').removeAttr('hidden');
        $('#conPropina').removeAttr('disabled');
        $('#propinaValue').removeAttr('disabled');
    } else {
        $('#propinaBruta').attr('hidden', true);
        $('#siPropina').attr('hidden', true);
        $('#conPropina').attr('disabled', true);
        $('#propinaValue').attr('disabled', true);
    }

    // También actualizar el total a pagar (servicios + consumo con o sin propina + diferencia)
    const diferencia = obtenerValorData('#diferencia', 'total-pagar');
    const totalPagar = totalServiciosConsumo + diferencia;
    const totalConsumoYServicios = totalServiciosConsumo;
    $('#total_pagar').val(formatCLP(totalPagar));

    // Actualizar valor consumo si está activo el checkbox "separar"
    // if ($('#separar').is(':checked')) {
    //     $('#valor_consumo').val(formatCLP(totalConsumoYServicios));
    //     if ($('#dividir_pago').is(':checked')) {
    //         // let mitad = Math.round(totalServiciosConsumo / 2);
    //         let mitad = Math.round(totalConsumoYServicios / 2);
    //         $('#valor_consumo1').val(formatCLP(mitad));
    //         $('#valor_consumo2').val(formatCLP(mitad));
    //     }
    // }

      if ($('#dividir_pago').is(':checked')) {
      $('#duplicar_pago').removeAttr('hidden');

      const diferencia = parseCurrency($('#diferencia').val());
      const consumoMasServicio = consumoFinal + servicios;
      const totalFinal = consumoMasServicio + diferencia;

      $('#valor_consumo1').val(formatCLP(diferencia));
      $('#valor_consumo2').val(formatCLP(consumoMasServicio));

      sincronizarPagosDivididos(totalFinal);
    }

}


  $(document).ready(function () {
      inicializarEstado();

      $('#propina, #propinaValue').on('input change', function () {
          setTimeout(() => {
              actualizarValores();
              recalcularTotalPagar();
          }, 100);
      });

      $('#diferencia').on('input', function () {
          recalcularTotalPagar();
      });

      setTimeout(recalcularTotalPagar, 500);
  });

  function inicializarEstado() {
    /**
    *! Usado para cargar los valores iniciales de los campos, se cambiara para considerar la diferencia
      var total = obtenerValorData('#total_pagar', 'total-pagar');
      var SinPropina = parseInt($('#sinPropina').val().replace(/\D/g, '')) || 0;
      var ConPropina = obtenerValorData('#conPropina', 'conpropina');
      var diferencia = obtenerValorData('#diferencia', 'total-pagar');
      
      // $('#total_pagar').val(formatCLP(total + SinPropina));
      
      $('#sinPropina').val(formatCLP(SinPropina));
      $('#diferencia').val(formatCLP(diferencia));
      $('#total_pagar').val(formatCLP(diferencia));
      
      if (ConPropina <= 0) {
        $('#check').attr('hidden', true);
        }
      **/
       
    var diferencia = obtenerValorData('#diferencia', 'total-pagar') || 0;
    var SinPropina = parseCurrency($('#sinPropina').val());
    var ConPropina = obtenerValorData('#conPropina', 'conpropina');

    $('#diferencia').val(formatCLP(diferencia));
    $('#total_pagar').val(formatCLP(diferencia));

    if (isNaN(SinPropina)) $('#sinPropina').val('$0');
    if (isNaN(ConPropina)) $('#conPropina').val('$0');

    if (ConPropina <= 0) {
        $('#check').attr('hidden', true);
    }
  }


/* Primero en funcionar
  $('#dividir_pago').on('change', function () {
    if ($(this).is(':checked')) {
      $('#duplicar_pago').removeAttr('hidden');

      // Calcular el total real de consumo + propina (si aplica) + servicio
      const propinaActiva = $('#propina').is(':checked');
      const consumoBruto = parseCurrency($('#consumo_bruto').val());
      const propina = parseCurrency($('#propinaValue').val());
      const servicios = parseCurrency($('#servicio_bruto').val());

      const consumoTotal = consumoBruto + (propinaActiva ? propina : 0);
      const totalConsumoServicios = consumoTotal + servicios;

      inicializarValoresDivididos(totalConsumoServicios);
      sincronizarPagosDivididos();
    } else {
      $('#duplicar_pago').attr('hidden', true);
      $('#valor_consumo1, #valor_consumo2').val('');
    }
  });

function inicializarValoresDivididos(total) {
  const mitad = Math.round(total / 2);
  $('#valor_consumo1').val(formatCLP(mitad));
  $('#valor_consumo2').val(formatCLP(total - mitad));
}

  function sincronizarPagosDivididos() {
    $('#valor_consumo1').off('input').on('input', function () {
      const val1 = parseCurrency($(this).val());

      // Volver a calcular el total real basado en el estado actual
      const propinaActiva = $('#propina').is(':checked');
      const consumoBruto = parseCurrency($('#consumo_bruto').val());
      const propina = parseCurrency($('#propinaValue').val());
      const servicios = parseCurrency($('#servicio_bruto').val());
      const total = consumoBruto + (propinaActiva ? propina : 0) + servicios;

      const val2 = total - val1;
      $('#valor_consumo2').val(formatCLP(val2));
    });

    $('#valor_consumo2').off('input').on('input', function () {
      const val2 = parseCurrency($(this).val());

      const propinaActiva = $('#propina').is(':checked');
      const consumoBruto = parseCurrency($('#consumo_bruto').val());
      const propina = parseCurrency($('#propinaValue').val());
      const servicios = parseCurrency($('#servicio_bruto').val());
      const total = consumoBruto + (propinaActiva ? propina : 0) + servicios;

      const val1 = total - val2;
      $('#valor_consumo1').val(formatCLP(val1));
    });
  }

*/

// Funcionando sin ocultar
// $('#dividir_pago').on('change', function () {
//   if ($(this).is(':checked')) {
//     $('#duplicar_pago').removeAttr('hidden');

//     const total = parseCurrency($('#total_pagar').val());
//     const diferencia = parseCurrency($('#diferencia').val());
//     const consumoServicio = total - diferencia;

//     $('#valor_consumo1').val(formatCLP(diferencia));
//     $('#valor_consumo2').val(formatCLP(consumoServicio));

//     sincronizarPagosDivididos(total);
//   } else {
//     $('#duplicar_pago').attr('hidden', true);
//     $('#valor_consumo1, #valor_consumo2').val('');
//   }
// });



$('#dividir_pago').on('change', function () {
  if ($(this).is(':checked')) {
    $('#duplicar_pago').removeAttr('hidden');

    // Oculta y desactiva los campos originales
    $('input[name="imagen_diferencia"]').closest('.file-field').hide();
    $('#id_tipo_transaccion_diferencia').closest('.input-field').hide();
    $('input[name="imagen_diferencia"]').prop('disabled', true);
    $('#id_tipo_transaccion_diferencia').prop('disabled', true);

    // Activa los campos de pago dividido
    $('input[name="imagen_diferencia_dividida"]').closest('.file-field').show();
    $('#id_tipo_transaccion_diferencia_dividida').closest('.input-field').show();
    $('input[name="imagen_diferencia_dividida"]').prop('disabled', false);
    $('#id_tipo_transaccion_diferencia_dividida').prop('disabled', false);

    // Actualiza los valores divididos
    const total = parseCurrency($('#total_pagar').val());
    const diferencia = parseCurrency($('#diferencia').val());
    const consumoServicio = total - diferencia;
    $('#valor_consumo1').val(formatCLP(diferencia));
    $('#valor_consumo2').val(formatCLP(consumoServicio));
    sincronizarPagosDivididos(total);
  } else {
    $('#duplicar_pago').attr('hidden', true);
    $('#valor_consumo1, #valor_consumo2').val('');

    // Muestra y activa los campos originales
    $('input[name="imagen_diferencia"]').closest('.file-field').show();
    $('#id_tipo_transaccion_diferencia').closest('.input-field').show();
    $('input[name="imagen_diferencia"]').prop('disabled', false);
    $('#id_tipo_transaccion_diferencia').prop('disabled', false);

    // Oculta y desactiva los campos de pago dividido
    $('input[name="imagen_diferencia_dividida"]').closest('.file-field').hide();
    $('#id_tipo_transaccion_diferencia_dividida').closest('.input-field').hide();
    $('input[name="imagen_diferencia_dividida"]').prop('disabled', true);
    $('#id_tipo_transaccion_diferencia_dividida').prop('disabled', true);
  }
});





function sincronizarPagosDivididos(total) {
  $('#valor_consumo1').off('input').on('input', function () {
    const val1 = parseCurrency($(this).val());
    const val2 = total - val1;
    $('#valor_consumo2').val(formatCLP(val2));
  });

  $('#valor_consumo2').off('input').on('input', function () {
    const val2 = parseCurrency($(this).val());
    const val1 = total - val2;
    $('#valor_consumo1').val(formatCLP(val1));
  });
}






//   $('#dividir_pago').on('change', function () {
//     if ($(this).is(':checked')) {
//         $('#duplicar_pago').removeAttr('hidden');

//         const totalConsumo = parseCurrency($('#valor_consumo').val());
//         const mitad = Math.round(totalConsumo / 2);

//         $('#valor_consumo1').val(formatCLP(mitad));
//         $('#valor_consumo2').val(formatCLP(mitad));

//         // Activar sincronización
//         sincronizarPagosDivididos();
//     } else {
//         $('#duplicar_pago').attr('hidden', true);
//         $('#valor_consumo1, #valor_consumo2').val('');
//     }
// });

// function sincronizarPagosDivididos() {
//     $('#valor_consumo1').on('input', function () {
//         let val1 = parseCurrency($(this).val());
//         const total = parseCurrency($('#valor_consumo').val());
//         let val2 = total - val1;
//         $('#valor_consumo2').val(formatCLP(val2));
//     });

//     $('#valor_consumo2').on('input', function () {
//         let val2 = parseCurrency($(this).val());
//         const total = parseCurrency($('#valor_consumo').val());
//         let val1 = total - val2;
//         $('#valor_consumo1').val(formatCLP(val1));
//     });
// }

</script>

<script>
  $(document).ready(function () {
    $('form').on('submit', function (){
      const $btn = $('#btn-guardar');
      $btn.prop('disabled', true);
      $btn.html('<i class="material-icons left">hourglass_empty</i>Guardando...');
    });
  });
</script>


@endsection