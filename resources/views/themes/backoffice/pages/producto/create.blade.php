@extends('themes.backoffice.layouts.admin')

@section('title','Ingresar Producto')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.producto.index') }}">Productos</a></li>
<li>Ingresar Producto</li>
@endsection



@section('content')

<div class="section">
    <p class="caption">Ingrese los datos para Registrar Producto.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header">Generar <strong>Producto</strong></h4>
                    <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.producto.store')}}">


                            {{csrf_field() }}



                            <div class="row">

                                <div class="input-field col s12 m6 l4">

                                    <label for="nombre">Nombre</label>
                                    <input id="nombre" type="text" name="nombre" class="" value="{{ old('nombre') }}">
                                    @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l4">

                                    <label for="valor">Valor</label>
                                    <input id="valor" type="number" name="valor" class="" value="{{ old('valor') }}">
                                    @error('valor')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>



                                <div class="input-field col s12 m6 l4">

                                    <select id="id_tipo_producto" name="id_tipo_producto" class=""
                                        value="{{ old('id_tipo_producto') }}">
                                        <option value="">-- Seleccione --</option>
                                        @foreach ($tipos as $tipo)
                                        <option value="{{$tipo->id}}" {{ old('id_tipo_producto') == $tipo->id ? 'selected' : '' }}>{{$tipo->nombre}}</option>
                                        @endforeach
                                    </select>
                                    <label for="id_tipo_producto">Tipo de Producto</label>

                                    @error('id_tipo_producto')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror

                                </div>

                            </div>


                            <div class="row">
                                <!-- Insumos y detalles -->
                                <div id="insumos-wrapper" class="col s12">
                                    {{-- <div class="insumo-item row">
                                        <div class="input-field col s12 m6 l4">
                                            <select name="insumos[0][id_insumo]" required>
                                                <option value="" disabled selected>Selecciona un insumo</option>
                                                @foreach($insumos as $insumo)
                                                <option value="{{ $insumo->id }}" {{old("insumos[0][id_insumo]") ==  $insumo->nombre ? 'selected' : ''}}>{{ $insumo->nombre
                                                    }}</option>
                                                @endforeach
                                            </select>
                                            <label for="insumos[0][id_insumo]">Insumo:</label>
                                            @error('insumos[0][id_insumo]')
                                            <span class="invalid-feedback" role="alert">
                                                <strong style="color:red">{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>


                                        <div class="input-field col s12 m6 l4">
                                            <label for="cantidad_insumo_usar_0">Cantidad Usar:</label>
                                            <input type="number" id="cantidad_insumo_usar_0"
                                                name="insumos[0][cantidad_insumo_usar]" required
                                                value="{{old('insumos[0][cantidad_insumo_usar_0]')}}">
                                            @error('insumos[0][cantidad_insumo_usar]')
                                            <span class="invalid-feedback" role="alert">
                                                <strong style="color:red">{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                        <div class="input-field col s12 m6 l4">
                                            <select name="insumos[0][id_unidad_medida]" required>
                                                <option value="" disabled selected>-- Seleccione --</option>
                                                @foreach($unidades as $unidad)
                                                <option value="{{ $unidad->id }}" {{$unidad->id ==
                                                    old('insumos[0][id_unidad_medida]') ? 'selected' : ''}}>{{
                                                    $unidad->nombre }}</option>
                                                @endforeach
                                            </select>
                                            <label for="insumos[0][id_unidad_medida]">Unidad de medida:</label>
                                            @error('insumos[0][id_unidad_medida]')
                                            <span class="invalid-feedback" role="alert">
                                                <strong style="color:red">{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                    </div> --}}


                                    
                                        {{-- @if(old('insumos'))
                                            @foreach(old('insumos') as $index => $insumo)
                                                <div class="insumo-item row">
                                                    <div class="input-field col s12 m6 l4">
                                                        <select name="insumos[{{ $index }}][id_insumo]" required>
                                                            <option value="" disabled>Selecciona un insumo</option>
                                                            @foreach($insumos as $insumoOption)
                                                                <option value="{{ $insumoOption->id }}" {{ $insumo['id_insumo'] == $insumoOption->id  ? 'selected' : '' }}>
                                                                    {{ $insumoOption->nombre }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <label for="insumos[{{ $index }}][id_insumo]">Insumo:</label>
                                                    </div>
                                    
                                                    <div class="input-field col s12 m6 l4">
                                                        <label for="cantidad_insumo_usar_{{ $index }}">Cantidad Usar:</label>
                                                        <input type="number" id="cantidad_insumo_usar_{{ $index }}" name="insumos[{{ $index }}][cantidad_insumo_usar]" value="{{ $insumo['cantidad_insumo_usar'] }}" required>
                                                    </div>
                                    
                                                    <div class="input-field col s12 m6 l4">
                                                        <select name="insumos[{{ $index }}][id_unidad_medida]" required>
                                                            <option value="" disabled>-- Seleccione --</option>
                                                            @foreach($unidades as $unidad)
                                                                <option value="{{ $unidad->id }}" {{ $unidad->id == $insumo['id_unidad_medida'] ? 'selected' : '' }}>
                                                                    {{ $unidad->nombre }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <label for="id_unidad_medida_{{ $index }}">Unidad de medida:</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif --}}

                                        @if(old('insumos'))
                                        @foreach(old('insumos') as $index => $insumo)
                                            <div class="insumo-item row">
                                                <div class="input-field col s12 m6 l4">
                                                    <select id="id_insumo_{{ $index }}" name="insumos[{{ $index }}][id_insumo]" required>
                                                        <option value="" disabled>Selecciona un insumo</option>
                                                        @foreach($insumos as $insumoOption)
                                                            <option value="{{ $insumoOption->id }}" {{ $insumoOption->id == $insumo['id_insumo'] ? 'selected' : '' }}>
                                                                {{ $insumoOption->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label for="id_insumo_{{$index}}">Insumo:</label>
                                                </div>
                                    
                                                <div class="input-field col s12 m6 l4">
                                                    <label for="cantidad_insumo_usar_{{ $index }}">Cantidad Usar:</label>
                                                    <input type="number" id="cantidad_insumo_usar_{{ $index }}" name="insumos[{{ $index }}][cantidad_insumo_usar]" value="{{ $insumo['cantidad_insumo_usar'] }}" required>
                                                </div>
                                    
                                                <div class="input-field col s12 m6 l4">
                                                    <select id="id_unidad_medida_{{ $index }}" name="insumos[{{ $index }}][id_unidad_medida]" required>
                                                        <option value="" disabled>-- Seleccione --</option>
                                                        @foreach($unidades as $unidad)
                                                            <option value="{{ $unidad->id }}" {{ $unidad->id == $insumo['id_unidad_medida'] ? 'selected' : '' }}>
                                                                {{ $unidad->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label for="id_unidad_medida_{{ $index }}">Unidad de medida:</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                    
                                    </div>
                                    
                                    
                                </div>

                                <button type="button" id="add-insumo-btn" class="btn">Agregar Insumo</button>
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




$(document).ready(function() {
    let insumoIndex = $('#insumos-wrapper .insumo-item').length; // Contar los insumos que ya están presentes por old()
    
    // Al hacer clic en "Agregar Insumo", añadir un nuevo insumo vacío
    $('#add-insumo-btn').off('click').on('click', function() {
        agregarInsumo(); // Llamamos a la función para añadir un nuevo insumo
    });

    // Función para agregar un nuevo insumo vacío
    function agregarInsumo() {
        let $wrapper = $('#insumos-wrapper');
        let nuevoIndex = insumoIndex++; // Incrementamos el índice

        let nuevoInsumo = `
            <div class="insumo-item row">
                <div class="input-field col s12 m6 l4">
                    <select id="id_insumo_${nuevoIndex}" name="insumos[${nuevoIndex}][id_insumo]" required>
                        <option value="" disabled selected>Selecciona un insumo</option>
                        @foreach($insumos as $insumoOption)
                            <option value="{{ $insumoOption->id }}" >{{ $insumoOption->nombre }}</option>
                        @endforeach
                    </select>
                    <label for="id_insumo_${nuevoIndex}">Insumo:</label>
                </div>

                <div class="input-field col s12 m6 l4">
                    <label for="cantidad_insumo_usar_${nuevoIndex}">Cantidad Usar:</label>
                    <input type="number" id="cantidad_insumo_usar_${nuevoIndex}" name="insumos[${nuevoIndex}][cantidad_insumo_usar]" required>
                </div>

                <div class="input-field col s12 m6 l4">
                    <select id="id_unidad_medida_${nuevoIndex}" name="insumos[${nuevoIndex}][id_unidad_medida]" required>
                        <option value="" disabled selected>-- Seleccione --</option>
                        @foreach($unidades as $unidad)
                            <option value="{{ $unidad->id }}">{{ $unidad->nombre }}</option>
                        @endforeach
                    </select>
                    <label for="id_unidad_medida_${nuevoIndex}">Unidad de medida:</label>
                </div>
            </div>
        `;

        // Añadir el nuevo insumo al contenedor
        $wrapper.append(nuevoInsumo);

        // Re-inicializar los selects con Materialize
        $wrapper.find('.insumo-item:last select').each(function() {
            M.FormSelect.init(this);
        });
    }
});






</script>

@endsection