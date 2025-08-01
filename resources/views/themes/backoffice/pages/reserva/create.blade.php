@extends('themes.backoffice.layouts.admin')

@section('title','Crear reserva')

@section('head')
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.date.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.time.css') }}">
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.cliente.show', $cliente->id) }}">Reservas del cliente</a></li>
<li>Crear Reserva</li>
@endsection



@section('content')

<div class="section">
  <p class="caption">Introduce los datos para crear un nuevo reserva</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
    <div class="row">
      <div class="col s12 m8 offset-m2 ">
        <div class="card-panel">
          <h4 class="header">Crear reserva para <strong>{{$cliente->nombre_cliente}}</strong>
          </h4>
          <div class="row">
            <form class="col s12" method="post" enctype="multipart/form-data"
            action="{{route('backoffice.reserva.store')}}">
            
            
            {{csrf_field() }}
            @if ($gc)
            <p class="blue-text"><i class='material-icons left'>info_outline</i> Existe una GiftCard asociada a este nombre. <a class="btn-small btn-floating" href="#" id="usarGC" data-programa="{{$gc->id_programa}}" data-cantidad="{{$gc->cantidad_personas}}" data-monto="{{$gc->monto}}" data-codigo="{{$gc->codigo}}"><i class='material-icons tiny'>file_download</i></a></p>
  
            <input type="hidden" name="gcard_id" id="gcard_id" value="{{$gc->id}}">
            @endif



              <div class="row">
                <div class="input-field col s12 m6">

                  <select name="id_programa" id="id_programa">
                    <option value="" disabled selected>-- Seleccione un programa --</option>
                    @foreach ($programas->sortBy('valor_programa') as $programa)
                    <option value="{{$programa->id}}" @if (old('id_programa')==$programa->id)
                      selected
                      @else

                      @endif data-valor="{{$programa->valor_programa}}"
                      data-incluye-masajes="{{ $programa->incluye_masajes ? '1' : '0' }}"
                      data-incluye-almuerzos="{{ $programa->incluye_almuerzos ? '1' : '0' }}"
                      >{{$programa->nombre_programa}}</option>
                    @endforeach
                  </select>
                  <label for="id_programa" class="black-text">Programa</label>
                  @error('id_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>



                <div class="input-field col s12 m6">
                  <input id="cliente_id" type="hidden" class="form-control" name="cliente_id" value="{{$cliente->id}}"
                    required>


                  <label for="cantidad_personas" class="black-text">Cantidad Personas</label>

                  <input id="cantidad_personas" type="number"
                    class="form-control @error('cantidad_personas') is-invalid @enderror" name="cantidad_personas"
                    value="{{old('cantidad_personas')}}" required>
                  @error('cantidad_personas')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>


              </div>


              <div class="row">

                <div class="input-field col s12 m3">

                  <label for="abono_programa" class="black-text">Cantidad de Abono</label>
                  <input id="abono_programa" type="text" name="abono_programa" class=""
                    value="{{ old('abono_programa') }}">
                    
                  <input type="hidden" id="abono_hidden">
                  @error('abono_programa')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

                <div class="file-field input-field col s12 m5">
                  <div class="btn">
                    <span>Imagen Abono</span>
                    <input type="file" id="imagen_abono" name="imagen_abono">
                  </div>
                  <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Seleccione su archivo">
                  </div>
                  @error('imagen_abono')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>


                <div class="input-field col s12 m4">
                  <select id="tipo_transaccion" name="tipo_transaccion">
                    <option disabled selected>-- Seleccione --</option>
                    @foreach ($tipos as $tipo)
                    <option value="{{ $tipo->id }}" @if (old('tipo_transaccion')==$tipo->id) selected @endif>{{ $tipo->nombre }}</option>
                    @endforeach
                  </select>
                  @error('tipo_transaccion')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                  <label for="tipo_transaccion">Tipo Transacción Abono</label>
                </div>

              </div>



              <div class="row">
                <div class="input-field col s12 m3">
                  <input id="fecha_visita" type="date" name="fecha_visita" class="" value="{{ old('fecha_visita') }}">
                  <label for="fecha_visita" class="black-text">Fecha Visita</label>
                  @error('fecha_visita')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="input-field col s12 m3">
                  <input id="observacion" name="observacion" type="text" class="" value="{{ old('observacion') }}" />
                  <label for="observacion">Cumpleaños, Aniversario, etc.</label>
                  @error('observacion')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>


                <label id="checkbox-masajes-container" class="input-field col s12 m3">
                  <input style="display: none" type="checkbox" id="agregar_masajes" name="agregar_masajes" />
                  <span class="black-text">¿Desea agregar masajes?</span>
                </label>


                <div class="input-field col s12 m3" id="input-cantidad-masajes-container">
                  <input id="cantidad_masajes_extra" type="number" name="cantidad_masajes_extra"
                    value="{{ old('cantidad_masajes_extra') }}">
                  <label for="cantidad_masajes_extra">Cantidad masajes extras</label>
                  @error('cantidad_masajes_extra')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror
                </div>


                <label id="checkbox-almuerzos-container" class="input-field col s12 m3">
                  <input style="display: none" type="checkbox" id="agregar_almuerzos" name="agregar_almuerzos" />
                  <span class="black-text">¿Desea agregar almuerzos?</span>
                </label>

              </div>

              <div class="row">
                <div class="input-field col s12 m3">

                  <label for="total_pagar">Total a pagar</label>
                  <input id="total_pagar" type="text" name="total_pagar" class="" value="{{old('total_pagar')}}"
                    placeholder="0" readonly>
                  @error('total_pagar')
                  <span class="invalid-feedback" role="alert">
                    <strong style="color:red">{{ $message }}</strong>
                  </span>
                  @enderror

                </div>

                <div class="input-field col s12 m6">
                  <label for="imagenSeleccionadaAbono">Imagen Abono:</label>
                  <img class="center-text" id="imagenSeleccionadaAbono" src="https://placehold.co/200x300?text=No+Image" alt=""
                    style="max-height: 200px">
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
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="{{ asset('assets/pickadate/lib/picker.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.date.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.time.js') }}"></script>

<script>
  $(document).ready(function () {

    $('#fecha_visita').pickadate({
      format: 'dd-mm-yyyy',
      formatSubmit: 'yyyy-mm-dd',
      hiddenName: true
    })

  });
</script>

{{-- <script>
  $(document).ready(function () {
    $('select').formSelect();
  });
</script> --}}

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
</script>

<script>
  var valorPrograma = 0;
  var cantidadPersonas = 1;
  var abono = 0;

$('#id_programa').on('change', function(){
  valorPrograma = parseInt($(this).find(':selected').data('valor')) || 0;
  calcularValorTotal();
});

$('#cantidad_personas').on('change', function(){
  cantidadPersonas = $(this).val();
  calcularValorTotal();
})

// $('#abono_programa').on('change', function(){
//   abono = parseInt($(this).val());
//   calcularValorTotal();
// })


$('#abono_programa').on('change', function(){
  let rawValue = $(this).val().replace(/\D/g, ''); // Eliminar caracteres no numéricos
      abono = parseInt(rawValue) || 0; 

      // Guardar el valor sin formato en un input oculto
      $('#abono_hidden').val(abono);

      // Mostrar el valor formateado en el input visible
      $(this).val(formatCLP(abono));
  calcularValorTotal();
})

function calcularValorTotal(){

  var total = (valorPrograma * cantidadPersonas)-abono;
  $('#abono_programa').val(formatCLP(abono));
  $('#total_pagar').val(formatCLP(total));
}

function formatCLP(number) 
{
      return isNaN(number) ? '$0' : '$' + parseInt(number, 10).toLocaleString('es-CL');
}
</script>


<script>
  $(document).ready(function(e){
    const selectPrograma = $('#id_programa');
    const cantidadMasajesInput = $('#cantidad_masajes').closest('div');
    const checkboxMasajesContainer = $('#checkbox-masajes-container');
    const inputCantidadMasajesContainer = $('#input-cantidad-masajes-container');
    const agregarMasajesCheckbox = $('#agregar_masajes');
    const cantidadMasajesExtraInput = $('#cantidad_masajes_extra');
    const checkboxAlmuerzosContainer = $('#checkbox-almuerzos-container');
    const agregarAlmuerzosCheckbox = $('#agregar_almuerzos');


    function toggleMasajesField() {
      const selectedOption = selectPrograma.find('option:selected');
      const incluyeMasajes = selectedOption.data('incluye-masajes');
      const inputMasajes = $('#cantidad_masajes');
      
      if (incluyeMasajes === 1) {
        // Si el programa incluye masajes, mostramos el input normal de cantidad de masajes
        cantidadMasajesInput.show();
        checkboxMasajesContainer.hide(); // Ocultar el checkbox de agregar masajes extras
        inputCantidadMasajesContainer.hide(); // Ocultar el input de cantidad de masajes extras
        cantidadMasajesExtraInput.val('');
        agregarMasajesCheckbox.prop('checked', false);
        inputMasajes.val('');
      } else {
        // Si no incluye masajes, ocultamos el input normal de masajes y mostramos el checkbox para agregar masajes extras
        cantidadMasajesInput.hide();
        checkboxMasajesContainer.show(); // Mostrar checkbox para agregar masajes
        inputCantidadMasajesContainer.hide(); // Inicialmente ocultamos el input de cantidad de masajes extras
        cantidadMasajesExtraInput.val('');
        agregarMasajesCheckbox.prop('checked', false);
        inputMasajes.val('');
      }
    }

    function toggleAlmuerzosField(){
      const selectedOption = selectPrograma.find('option:selected');
      const incluyeAlmuerzos = selectedOption.data('incluye-almuerzos');
      
      if (incluyeAlmuerzos === 1) {
        checkboxAlmuerzosContainer.hide();
        agregarAlmuerzosCheckbox.prop('checked', false);
      } else {
        checkboxAlmuerzosContainer.show();
      }
    }

    // Mostrar el input de cantidad de masajes solo si el checkbox está marcado
    agregarMasajesCheckbox.on('change', function() {
      if ($(this).is(':checked')) {
        inputCantidadMasajesContainer.show(); // Mostrar el input de cantidad si el checkbox está marcado
      } else {
        inputCantidadMasajesContainer.hide(); // Ocultar el input si el checkbox está desmarcado
        cantidadMasajesExtraInput.val('');
      }
    });

    // Escucha el evento change del select para detectar cuando cambie
    selectPrograma.on('change', toggleMasajesField);
    selectPrograma.on('change', toggleAlmuerzosField);

    // Inicializa el estado del campo en la carga de la página
    toggleMasajesField(); // Para verificar la selección inicial
    toggleAlmuerzosField();
  
  });

</script>


<script>
  $(document).ready(function () {
    $('#fecha_visita').on('change',function () { 
      const fechaSeleccionada = $(this).val();

      // Hacer una solicitud AJAX para verificar la disponibilidad de ubicaciones
      $.ajax({
        url: '{{ route("backoffice.verificar.ubicaciones") }}',
        type: 'GET',
        data: { fecha: fechaSeleccionada },
        success: function (response) {
          
          if (response.length == 0) {
            // Mostrar una alerta si no hay ubicaciones Disponibles
            const Toast = Swal.mixin({
                      toast: true,
                      position: "center",
                      showConfirmButton: false,
                      timer: 3000,
                      timerProgressBar: true,
                      didOpen: (toast) => {
                          toast.onmouseenter = Swal.stopTimer;
                          toast.onmouseleave = Swal.resumeTimer;
                      }
                  });
                  
                  Toast.fire({
                      icon: "error",
                      title: "Este dia, no cuenta con ubicaciones disponibles"
                  });
                
                  $('#fecha_visita').val('');
          } 
        },
        error: function () {
          // alert('Hubo un error al verificar la disponibilidad.');
          console.log('Se produjo un error en la fecha');
          
        }
      });

    });
  });
</script>

{{-- Desde el boton --}}
<script>
  $('#usarGC').on('click', function (e) {
      e.preventDefault();

      let programa = $(this).data('programa');
      let cantidad = $(this).data('cantidad');
      let monto = $(this).data('monto');
      let codigo = $(this).data('codigo');

      // Seleccionar programa y disparar eventos
      $('#id_programa').val(programa);
      $('#id_programa').material_select();
      $('#id_programa').trigger('change');

      // Cantidad de personas
      $('#cantidad_personas').val(cantidad).trigger('change');
      $('label[for="cantidad_personas"]').addClass('active');

      // Abono
      abono = parseInt(monto);
      $('#abono_programa').val(formatCLP(monto));
      $('#abono_hidden').val(monto);
      $('label[for="abono_programa"]').addClass('active');

      // Calcular total
      calcularValorTotal();

      // Generar código de barras
      let canvas = document.createElement('canvas');
      JsBarcode(canvas, codigo, { format: "CODE128" });
      $('#imagenSeleccionadaAbono').attr('src', canvas.toDataURL("image/png"));
  });
</script>

{{-- Desde el ByPass de Gift Card --}}
<script>
  document.addEventListener('DOMContentLoaded', function () {
      @if(request()->has('programa'))
          // Simula el click pasando los valores del query
          let programa = "{{ request('programa') }}";
          let cantidad = "{{ request('cantidad') }}";
          let monto    = "{{ request('monto') }}";
          let codigo   = "{{ request('codigo') }}";

          // Reutiliza la misma lógica que tenías en #usarGC
          $('#id_programa').val(programa);
          $('#id_programa').material_select();
          $('#id_programa').trigger('change');

          $('#cantidad_personas').val(cantidad).trigger('change');
          $('label[for="cantidad_personas"]').addClass('active');

          abono = parseInt(monto);
          $('#abono_programa').val(formatCLP(monto));
          $('#abono_hidden').val(monto);
          $('label[for="abono_programa"]').addClass('active');

          calcularValorTotal();

          let canvas = document.createElement('canvas');
          JsBarcode(canvas, codigo, { format: "CODE128" });
          $('#imagenSeleccionadaAbono').attr('src', canvas.toDataURL("image/png"));
      @endif
  });
</script>


@endsection