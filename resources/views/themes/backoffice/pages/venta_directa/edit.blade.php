@extends('themes.backoffice.layouts.admin')

@section('title', 'Venta Directa')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="">Venta directa</a></li> --}}
@endsection

@section('dropdown_settings')
{{-- Opciones adicionales aquí --}}
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Modificando Venta Directa</strong></p> 
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">

                    <h4 class="header">Seleccione categoria y cantidad del producto</h4>
                    <div class="row">
                        <form class="col s12" method="post"
                            action="{{route('backoffice.venta_directa.update', $ventaDirecta)}}">
                            {{csrf_field()}}
                            {{ method_field('PUT') }}

                            <div class="row">

                                <div class="card col s12">
                                    <div class="card-content gradient-45deg-light-blue-cyan">
                                        <h5 class="white-text" id="nombreSeleccion">nombre tipo</h5>
                                    </div>

                                    <div class="card-tabs">
                                        <ul class="tabs tabs-fixed-width">
                                            @foreach($tipos->sortBy('nombre') as $tipo)
                                            @if(in_array($tipo->nombre, $listado))
                                            <li class="tab"><a href="#tipo_{{$tipo->id}}"
                                                    id="seleccion">{{$tipo->nombre}}</a></li>
                                            @endif
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div class="card-content grey lighten-4">
                                        @foreach($tipos->sortBy('nombre') as $tipo)
                                        @if(in_array($tipo->nombre, $listado))
                                        <div id="tipo_{{$tipo->id}}" class="tipo-section">
                                            <div class="row">
                                                @foreach($tipo->productos->sortBy('nombre') as $producto)
                                                <div class="col s12 m6">
                                                    <blockquote>
                                                        <h5>{{ $producto->nombre }}</h5>
                                                    </blockquote>
                                                </div>

                                                <div class="col s12 m2">
                                                    <h5>${{ $producto->valor }}</h5>
                                                    <input id="productos_{{ $producto->id }}" type="hidden"
                                                        name="productos[{{ $producto->id }}][valor]"
                                                        value="{{ $producto->valor }}">

                                                    @error('productos.'.$producto->id.'.valor')
                                                    <span class="invalid-feedback" role="alert">
                                                      <strong style="color:red">{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </div>

                                                <div class="input-field col s12 m4">
                                                    <label for="producto_{{ $producto->id }}">Cantidad</label>
                                                    @php
                                                        $detalle = $ventaDirecta->detalles->firstWhere('producto_id', $producto->id);
                                                    @endphp

                                                    <input id="producto_{{ $producto->id }}" type="number"
                                                        name="productos[{{ $producto->id }}][cantidad]"
                                                        value="{{ old('productos.'.$producto->id.'.cantidad', optional($detalle)->cantidad ?? '') }}">

                                                        @error('productos.'.$producto->id.'.cantidad')
                                                        <span class="invalid-feedback" role="alert">
                                                          <strong style="color:red">{{ $message }}</strong>
                                                        </span>
                                                            
                                                        @enderror
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        @endforeach
                                    </div>
                                </div>


                            </div>







                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <select name="id_tipo_transaccion" id="id_tipo_transaccion">
                                      <option selected disabled>-- Seleccione --</option>
                                      @foreach ($tiposTransacciones as $tipo)
                                      {{-- <option value="{{$tipo->id}}">{{$tipo->nombre}}</option> --}}
                                      <option value="{{$tipo->id}}" {{ old('id_tipo_transaccion', $ventaDirecta->id_tipo_transaccion ?? '') == $tipo->id ? 'selected' : '' }}>
                                        {{$tipo->nombre}}
                                        </option>
                                      @endforeach
                                    </select>
                                    @error('id_tipo_transaccion')
                                    <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                    <label for="id_tipo_transaccion">Tipo Transaccion Consumo</label>
                
                                  </div>


                                  <label for="tiene_propina">Incluye Propina:</label>
                                  <div class="switch col s12 m6">
                                    <label class="black-text">
                                      No
                                      <input type="hidden" name="tiene_propina" value="0">
                                      {{-- <input type="checkbox" name="tiene_propina" id="tiene_propina" value="1" {{ old('tiene_propina') ? 'checked' : '' }}> --}}
                                      <input type="checkbox" name="tiene_propina" id="tiene_propina" value="1" {{ old('tiene_propina', $ventaDirecta->tiene_propina ?? 0) ? 'checked' : '' }}>

                                      <span class="lever black-text"></span>
                                      Si
                                    </label>
                                    @error('tiene_propina')
                                    <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                  </div>

                                
                            </div>

                            <div class="row">
                                <div class="input-field col s12 m4 right">
                                    {{-- <input id="subtotal_visible" class="black-text" type="text" value="{{ old('subtotal') ? '$' . number_format(old('subtotal'), 0, ',', '.') : '$0' }}" disabled> --}}
                                    <input id="subtotal_visible" class="black-text" type="text" value="{{ '$' . number_format(old('subtotal', $ventaDirecta->subtotal ?? 0), 0, ',', '.') }}" disabled>
                                    <label class="active black-text">Subtotal</label>
                                    {{-- SUBTOTAL REAL --}}
                                    {{-- <input type="hidden" id="subtotal" name="subtotal" value="{{ old('subtotal', 0) }}"> --}}
                                    <input type="hidden" id="subtotal" name="subtotal" value="{{ old('subtotal', $ventaDirecta->subtotal ?? 0) }}">
                                    @error('subtotal')
                                    <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12 m4 right">
                                    {{-- <input id="propina" name="propina" type="text" step="100"> --}}
                                    <input id="propina" name="propina" type="text" value="{{ '$' . number_format(old('propina', $ventaDirecta->valor_propina ?? 0), 0, ',', '.') }}">

                                    {{-- {{dd($ventaDirecta->valor_propina)}} --}}

                                    <label for="propina" class="active">Propina sugerida (10%)</label>
                                    @error('propina')
                                    <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                            </div>
                            <div class="row">
                                <div class="input-field col s12 m4 right">
                                    {{-- <input id="total_visible" class="black-text" type="text" value="{{ old('total') ? '$' . number_format(old('total'), 0, ',', '.') : '$0' }}" disabled> --}}
                                    <input id="total_visible" class="black-text" type="text" value="{{ '$' . number_format(old('total', $ventaDirecta->total ?? 0), 0, ',', '.') }}" disabled>
                                    <label class="active black-text">Total</label>
                                    {{-- TOTAL REAL --}}
                                    {{-- <input type="hidden" id="total" name="total" value="{{ old('total', 0) }}"> --}}
                                    <input type="hidden" id="total" name="total" value="{{ old('total', $ventaDirecta->total ?? 0) }}">
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
                                        <i class="material-icons right">update</i>
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
    $(document).ready(function() {
        $('select').material_select();
    });
</script>

<script>
    $(document).ready(function(){
$('.tabs').tabs();
});
    $(document).ready(function () {
        // let nombreTipo = $('#nombreSeleccion').text();
        let seleccion = $('#seleccion').text();
        
        
            // Obtener el texto del primer tab visible y mostrarlo en el h5 al cargar la página
            let nombreInicial = $('.tabs .tab a.active').text();
            if (!nombreInicial) {
                nombreInicial = $('.tabs .tab a:first').text();
            }
            $('#nombreSeleccion').text(nombreInicial); // Mostrar el nombre inicial

            // Escuchar el evento click en las pestañas
            $('.tabs .tab a').on('click', function (e) {
                e.preventDefault();

                // Obtener el texto del enlace seleccionado
                let nombreTipo = $(this).text();

                // Actualizar el contenido del h5 con id "nombreSeleccion"
                $('#nombreSeleccion').text(nombreTipo);
            });
    });
</script>


<script>

$(document).ready(function() {
    let propinaModificadaManualmente = false;

    function formatCLP(valor) {
        return '$' + valor.toLocaleString('es-CL');
    }

    function calcularTotales() {
        let subtotal = 0;

        // Calcular subtotal
        $('input[type="number"][id^="producto_"]').each(function () {
            const cantidad = parseInt($(this).val());
            if (!isNaN(cantidad) && cantidad > 0) {
                const id = $(this).attr('id').split('_')[1];
                const valor = parseFloat($(`#productos_${id}`).val());
                subtotal += valor * cantidad;
            }
        });

        // Mostrar y guardar subtotal
        $('#subtotal').val(subtotal); // oculto
        $('#subtotal_visible').val(formatCLP(subtotal)); // visible

        const incluirPropina = $('#tiene_propina').is(':checked');
        let propina = 0;

        if (incluirPropina) {
            if (!propinaModificadaManualmente) {
                propina = Math.round(subtotal * 0.1);
                $('#propina').val(formatCLP(propina));
            } else {
                // Obtener propina manual ingresada, limpiar formato
                const limpia = $('#propina').val().replace(/\D/g, '');
                propina = parseInt(limpia) || 0;
                $('#propina').val(formatCLP(propina)); // re-formatear lo que el usuario escribe
            }
        } else {
            propina = 0;
            $('#propina').val(formatCLP(0));
            propinaModificadaManualmente = false;
        }

        const total = subtotal + propina;
        $('#total').val(total); // oculto
        $('#total_visible').val(formatCLP(total)); // visible
    }

    // Detectar cambios en cantidad de productos
    $(document).on('input', 'input[type="number"][id^="producto_"]', calcularTotales);

    // Detectar si el usuario modifica la propina manualmente
    $('#propina').on('input', function () {
        propinaModificadaManualmente = true;
        calcularTotales();
    });

    // Si se activa/desactiva el checkbox, recalcular y resetear bandera
    $('#tiene_propina').on('change', function () {
        propinaModificadaManualmente = false;
        calcularTotales();
    });

    // Solo calcular automáticamente si no hay valores cargados
    let valoresCargados = {{ isset($ventaDirecta) ? 'true' : 'false' }};
    if (!valoresCargados) {
        calcularTotales();
    }
});
</script>

@endsection
