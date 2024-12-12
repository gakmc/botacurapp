@extends('themes.backoffice.layouts.admin')

@section('title', 'Ingresar Consumo')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $venta->id_reserva) }}">Consumo para reserva del cliente</a></li>
<li>Ingresar Consumo</li>
@endsection

@section('content')

<div class="section">
    <p class="caption">Ingrese los datos del consumo para la venta.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header">Consumo para la venta de
                        <strong>{{$venta->reserva->cliente->nombre_cliente}}</strong>
                    </h4>
                    <div class="row">
                        <form class="col s12" method="post"
                            action="{{route('backoffice.venta.consumo.store', $venta)}}">
                            {{csrf_field()}}



                            <div class="row">
                                <div class="input-field col s12 m6 l4" hidden>
                                    <input id="id_venta" type="hidden" name="id_venta" value="{{$venta->id}}" required>
                                </div>

                                {{-- <div class="card">
                                    <div class="card-content gradient-45deg-light-blue-cyan">
                                        <h5 class="white-text">nombre tipo</h5>
                                    </div>

                                    <div class="card-tabs">
                                        <ul class="tabs tabs-fixed-width">
                                            @foreach($tipos as $tipo)
                                            <li class="tab"><a href="#tipo_{{$tipo->id}}">{{$tipo->nombre}}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>


                                    <div class="card-content grey lighten-4">
                                        <div class="row">
                                            @foreach($tipos as $tipo)
                                            @foreach($tipo->productos as $producto)
                                            <div class="col s6" id="tipo_{{$producto->id_tipo_producto}}">
                                                <blockquote>
                                                    <h5>
                                                        {{$producto->nombre}}
                                                    </h5>
                                                </blockquote>
                                            </div>
                                            <div class="col s2" id="tipo_{{$producto->id_tipo_producto}}">

                                                <h5>
                                                    ${{$producto->valor}}
                                                </h5>
                                                <input id="productos_{{ $producto->id }}" type="hidden"
                                                    name="productos[{{ $producto->id }}][valor]"
                                                    value="{{$producto->valor}}">

                                            </div>
                                            <div class="input-field col s2 offset-s2">
                                                <label for="producto_{{ $producto->id }}">Cantidad</label>
                                                <input id="producto_{{ $producto->id }}" type="number"
                                                    name="productos[{{ $producto->id }}][cantidad]" value="">
                                            </div>


                                            @endforeach
                                            @endforeach
                                        </div>


                                    </div>
                                </div> --}}


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
                                                </div>

                                                <div class="input-field col s12 m4">
                                                    <label for="producto_{{ $producto->id }}">Cantidad</label>
                                                    <input id="producto_{{ $producto->id }}" type="number"
                                                        name="productos[{{ $producto->id }}][cantidad]" value="">
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


                                <!-- Listar productos por categorías -->


                                {{-- <div class="input-field col s12">
                                    <label for="observacion">Observaciones</label>
                                    <input id="observacion" type="text" name="observacion"
                                        value="{{ old('observacion') }}">
                                    @error('observacion')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div> --}}

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
@endsection