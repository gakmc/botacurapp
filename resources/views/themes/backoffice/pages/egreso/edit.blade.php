@extends('themes.backoffice.layouts.admin')

@section('title', 'Modificar Egresos')

@section('head')
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.date.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.time.css') }}">
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos</a></li>
<li><a href="{{route('backoffice.egreso.mes',['anio'=>$anio, 'mes'=>$mes])}}">Egresos {{ucfirst(\Carbon\Carbon::create()->month($mes)->year($anio)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</a></li>
<li>Modificando Egreso {{ucfirst(\Carbon\Carbon::create()->month($mes)->day($dia)->locale('es')->isoFormat('DD [de] MMMM'))}}</li>
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.egreso.create') }}" class="grey-text text-darken-2">Crear Egreso</a></li> --}}
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Modificando egreso</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header2">Modificar Egreso</h4>
                    <div class="row">




                        <form action="{{ route('backoffice.egreso.update', $egreso) }}" method="POST">
                          @csrf
                          @method('PUT')

                          <div class="row">

                            <div class="input-field col s12 m4">
                              <select id="tipo_documento_id" name="tipo_documento_id" required>
                                <option value="" disabled>-- Selecciona tipo de documento --</option>
                                @foreach ($tipoDocumentos as $tipo)
                                    <option value="{{$tipo->id}}" {{$egreso->tipo_documento_id == $tipo->id ? 'selected' : ''}}>{{$tipo->nombre}}</option>
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
                                <option value="" disabled>-- Selecciona categoría --</option>
                                @foreach ($categorias as $categoria)
                                  <option value="{{ $categoria->id }}" {{$egreso->categoria_id == $categoria->id ? 'selected' : ''}}>{{ $categoria->nombre }}</option>
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
                              <option value="" disabled>-- Selecciona subcategoría --</option>
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
                                <input type="text" name="folio" placeholder="Ej: 123456" value="{{$egreso->folio ?? ''}}">
                                <label for="folio">Folio (si es factura)</label>
                                @error('folio')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                @enderror
                              </div>

                              <div class="input-field col s12 m4">
                                <input type="text" id="fecha" name="fecha" value="{{ \Carbon\Carbon::parse($egreso->fecha)->format('d-m-Y') }}" required>

                                <label class="active">Fecha de emisión</label>
                                @error('fecha')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                @enderror
                              </div>

                              <div class="input-field col s12 m4">
                                <select name="proveedor_id">
                                  <option value="" disabled {{ is_null($egreso->proveedor_id) ? 'selected' : '' }}>-- Seleccione proveedor --</option>
                                  @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" {{$egreso->proveedor_id == $proveedor->id ? 'selected' : ''}}>{{ $proveedor->nombre }}</option>
                                  @endforeach
                                </select>
                                <label>Proveedor</label>
                                @error('proveedor_id')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                @enderror
                              </div>

                          </div>


                          <div class="row">

                              <div class="input-field col s12 m4">
                                <input type="text" id="neto" name="neto" value="{{number_format($egreso->neto,0,'','.')}}">
                                <label for="neto">Monto Neto (solo factura)</label>
                                @error('neto')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                @enderror
                              </div>

                              <div class="input-field col s12 m4">
                                <input type="text" id="iva" name="iva" value="{{number_format($egreso->iva,0,'','.')}}">
                                <label for="iva">IVA (0.19 × neto)</label>
                                @error('iva')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                @enderror
                              </div>

                              <div id="divImpuesto" class="input-field col s12 m4" hidden>
                                <input type="text" id="impuesto_incluido" name="impuesto_incluido" disabled>
                                <label id="lblImpuesto" for="impuesto_incluido">Impuesto adicional</label>
                                @error('impuesto_incluido')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                @enderror
                              </div>

                              <div class="input-field col s12 m4">
                                <input type="text" id="total" name="total" required value="{{number_format($egreso->total,0,'','.')}}">
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
                              <button class="btn waves-effect waves-light right" type="submit">Actualizar
                                <i class="material-icons right">save</i>
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
<script src="{{ asset('assets/pickadate/lib/picker.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.date.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.time.js') }}"></script>

<script>
$(document).ready(function () {

    // Inicializar datepicker
    $('#fecha').pickadate({
        format: 'dd-mm-yyyy',
        formatSubmit: 'yyyy-mm-dd',
        hiddenName: true
    });

    // Inicializar selects
    $('select').material_select();

    // Evitar propagación en subcategoria
    setTimeout(function () {
        let subWrapper = $('#subcategoria_select').parent('.select-wrapper');
        subWrapper.on('click', function (e) {
            e.stopPropagation();
        });
    }, 500);

    // Cargar subcategorías dinámicamente al cambiar categoría
    $('#categoria_select').on('change', function () {
        let categoriaId = $(this).val();
        $.get('/subcategorias/' + categoriaId, function (data) {
            let subSelect = $('#subcategoria_select');
            subSelect.empty().append('<option disabled selected>-- Selecciona subcategoría --</option>');
            data.forEach(function (item) {
                subSelect.append('<option value="' + item.id + '" data-name="' + item.nombre + '">' + item.nombre + '</option>');
            });
            subSelect.material_select();

            // Volver a aplicar el stopPropagation
            setTimeout(function () {
                let subWrapper = $('#subcategoria_select').parent('.select-wrapper');
                subWrapper.on('click', function (e) {
                    e.stopPropagation();
                });
            }, 100);
        });
    });

    // Preseleccionar subcategoría y categoría
    const subcategoriaIdActual = {{ $egreso->subcategoria_id ?? 'null' }};
    const categoriaIdActual = {{ $egreso->categoria_id ?? 'null' }};

    if (categoriaIdActual) {
        $('#categoria_select').val(categoriaIdActual);
        $('#categoria_select').material_select();

        $.get('/subcategorias/' + categoriaIdActual, function (data) {
            let subSelect = $('#subcategoria_select');
            subSelect.empty().append('<option disabled>-- Selecciona subcategoría --</option>');
            data.forEach(function (item) {
                let selected = (item.id === subcategoriaIdActual) ? 'selected' : '';
                subSelect.append('<option value="' + item.id + '" ' + selected + ' data-name="' + item.nombre + '">' + item.nombre + '</option>');
            });
            subSelect.material_select();

            setTimeout(function () {
                let subWrapper = $('#subcategoria_select').parent('.select-wrapper');
                subWrapper.on('click', function (e) {
                    e.stopPropagation();
                });
            }, 100);

            // Forzar cálculo inicial
            $('#subcategoria_select').trigger('change');
        });
    }

    // Funciones para formateo CLP
    function formatCLP(valor) {
        return '$' + valor.toLocaleString('es-CL');
    }
    function limpiarNumero(valor) {
        return parseInt(valor.replace(/[$.]/g, '')) || 0;
    }

    // Calcular IVA y total
    $('#neto').change(function (e) {
        e.preventDefault();
        var neto = limpiarNumero($('#neto').val());
        var iva = parseInt(neto * 0.19);
        var seleccion = $('#subcategoria_select option:selected').data('name') || '';
        var valorImp = 0;
        var impCarnes = 0.05;
        var impCerveza = 0.205;
        var total = 0;

        if (seleccion.toLowerCase() == 'carnes') {
            valorImp = parseInt(neto * impCarnes);
            total = neto + valorImp + iva;
        } else if (seleccion.toLowerCase() == 'cervezas' || seleccion.toLowerCase() == 'botilleria') {
            valorImp = parseInt(neto * impCerveza);
            total = neto + valorImp + iva;
        } else {
            total = neto + iva;
        }

        $('#neto').val(formatCLP(neto));
        $('#iva').val(formatCLP(iva));

        if (valorImp > 0) {
            $('#impuesto_incluido').val(formatCLP(valorImp));
            $('#divImpuesto').removeAttr('hidden');
            $('#impuesto_incluido').removeAttr('disabled');
        } else {
            $('#impuesto_incluido').val('');
            $('#divImpuesto').attr('hidden', true);
            $('#impuesto_incluido').attr('disabled', true);
        }

        $('#total').val(formatCLP(total));

        $('label[for="neto"]').addClass('active');
        $('label[for="iva"]').addClass('active');
        $('#lblImpuesto').addClass('active');
        $('label[for="total"]').addClass('active');
    });

    // Formatear total manual
    $('#total').change(function (e) {
        var soloTotal = limpiarNumero($('#total').val());
        $('#total').val(formatCLP(soloTotal));
    });

    // Mostrar/Ocultar impuesto al cambiar subcategoría
    $('#subcategoria_select').on('change', function(e){
        e.stopPropagation();
        e.preventDefault();
        var seleccionado = $('#subcategoria_select option:selected').data('name') || '';
        var impuesto = $('#impuesto_incluido');
        var lblImpuesto = $('#lblImpuesto');
        var divImpuesto = $('#divImpuesto');
        
        if (seleccionado.toLowerCase() == 'carnes') {
            divImpuesto.removeAttr('hidden');
            impuesto.removeAttr('disabled');
            lblImpuesto.text('Impuesto Carnes (5%)');
        } else if (seleccionado.toLowerCase() == 'cervezas' || seleccionado.toLowerCase() == 'botilleria') {
            divImpuesto.removeAttr('hidden');
            impuesto.removeAttr('disabled');
            lblImpuesto.text('Impuesto Alcohol (20,5%)');
        } else {
            divImpuesto.attr('hidden', true);
            impuesto.attr('disabled', true);
            impuesto.val('');
            lblImpuesto.text('Impuesto Adicional');
        }

        $('#neto').trigger('change');
    });

    // Mostrar impuesto si ya tiene valor guardado (>0)
    var impuestoVal = $('#impuesto_incluido').val();
    if (impuestoVal && limpiarNumero(impuestoVal) > 0) {
        $('#divImpuesto').removeAttr('hidden');
        $('#impuesto_incluido').removeAttr('disabled');
        $('#lblImpuesto').addClass('active');
    }

});
</script>
@endsection

