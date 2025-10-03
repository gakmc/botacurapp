@extends('themes.backoffice.layouts.admin')

@section('title', 'Generar Egresos')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos</a></li>
<li>Generando Egreso</li>
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li> --}}
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Generando nuevo egreso</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header2">Crear Egreso</h4>
                    <div class="row">


  <form action="{{ route('backoffice.egreso.store') }}" method="POST">
    @csrf

    <div class="row">

      <div class="input-field col s12 m4">
        <select id="tipo_documento_id" name="tipo_documento_id" required>
          <option value="" disabled selected>-- Selecciona tipo de documento --</option>
          @foreach ($tipoDocumentos as $tipo)
              <option value="{{$tipo->id}}">{{$tipo->nombre}}</option>
          @endforeach
        </select>
        <label for="tipo_documento_id">Tipo de Documento</label>
          @error('tipo_documento_id')
            <span class="invalid-feedback" role="alert">
                <strong style="color:red">{{ $message }}</strong>
            </span>
          @enderror
      </div>

      <div class="input-field col s12 m4">
        <select id="categoria_select" name="categoria_id" required>
          <option value="" disabled selected>-- Selecciona categoría --</option>
          @foreach ($categorias as $categoria)
            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
          @endforeach
        </select>
        <label for="categoria_id">Categoría</label>
          @error('categoria_id')
            <span class="invalid-feedback" role="alert">
                <strong style="color:red">{{ $message }}</strong>
            </span>
          @enderror
      </div>

      <div class="input-field col s12 m4">
      <select id="subcategoria_select" name="subcategoria_id" required>
        <option value="" disabled selected>-- Selecciona subcategoría --</option>
      </select>
      <label for="subcategoria_id">Subcategoría</label>
          @error('subcategoria_id')
            <span class="invalid-feedback" role="alert">
                <strong style="color:red">{{ $message }}</strong>
            </span>
          @enderror
      </div>

    </div>


    <div class="row">

        
        <div class="input-field col s12 m4">
          <select name="proveedor_id">
            <option value="" selected disabled>-- Seleccione proveedor --</option>
            @foreach ($proveedores as $proveedor)
              <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
            @endforeach
          </select>
          <label>Proveedor</label>
          @error('proveedor_id')
            <span class="invalid-feedback" role="alert">
                <strong style="color:red">{{ $message }}</strong>
            </span>
          @enderror
        </div>

        <div class="input-field col s12 m4">
          <input type="text" id="total" name="total" required>
          <label for="total">Total</label>
          @error('total')
            <span class="invalid-feedback" role="alert">
                <strong style="color:red">{{ $message }}</strong>
            </span>
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
    // Inicializar selects
    $('select').material_select();

    // Evitar propagación en subcategoria
    setTimeout(function () {
        // Obtener el wrapper del subcategoria_select
        let subWrapper = $('#subcategoria_select').parent('.select-wrapper');
        subWrapper.on('click', function (e) {
            e.stopPropagation();
        });
    }, 500);

    // Cargar subcategorías al cambiar categoría
    $('#categoria_select').on('change', function () {
        let categoriaId = $(this).val();
        $.get('/subcategorias/' + categoriaId, function (data) {
            let subSelect = $('#subcategoria_select');
            subSelect.empty().append('<option disabled selected>-- Selecciona subcategoría --</option>');
            data.forEach(function (item) {
                subSelect.append('<option value="' + item.id + '" data-name="' + item.nombre + '">' + item.nombre + '</option>');
            });
            subSelect.material_select();

            // Volver a aplicar el stopPropagation al nuevo select-wrapper
            setTimeout(function () {
                let subWrapper = $('#subcategoria_select').parent('.select-wrapper');
                subWrapper.on('click', function (e) {
                    e.stopPropagation();
                });
            }, 100);
        });
    });
});
</script>

<script>
$(document).ready(function () {
  function formatCLP(valor) {
    return '$' + valor.toLocaleString('es-CL');
  }

  function limpiarNumero(valor) {
    return parseInt(valor.replace(/[$.]/g, '')) || 0;
  }


  $('#total').change(function (e) {
    var soloTotal = limpiarNumero($('#total').val());
    $('#total').val(formatCLP(soloTotal));
  });
});
</script>

{{-- <script>
  $(document).ready(function () {
    $('#subcategoria_select').on('change', function(e){
      e.stopPropagation();
      e.preventDefault();
      var seleccionado = $('#subcategoria_select option:selected').data('name');
      var impuesto = $('#impuesto_incluido');
      var lblImpuesto = $('#lblImpuesto');
      var divImpuesto = $('#divImpuesto');
      console.log(seleccionado);
      
      if (seleccionado.toLowerCase() == 'carnes') {
        divImpuesto.removeAttr('hidden');
        impuesto.removeAttr('disabled');
        lblImpuesto.text('Impuesto Carnes (5%)');
      }else if(seleccionado.toLowerCase() == 'cervezas' || seleccionado.toLowerCase() == 'botilleria'){
        divImpuesto.removeAttr('hidden');
        impuesto.removeAttr('disabled');
        lblImpuesto.text('Impuesto Alcohol (20,5%)');
      }else{
        divImpuesto.attr('hidden', true);
        impuesto.attr('disabled', true);
        impuesto.val('');
        lblImpuesto.text('Impuesto Adicional');
      }

      $('#neto').trigger('change');

    })
  });
</script> --}}
@endsection
