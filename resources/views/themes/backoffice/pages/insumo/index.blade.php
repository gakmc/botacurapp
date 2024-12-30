@extends('themes.backoffice.layouts.admin')

@section('title', '')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.insumo.index')}}">Insumos</a></li> 
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.insumo.create') }}" class="grey-text text-darken-2">Ingresar Insumo</a></li>
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Insumos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">

            {{-- Cocina --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Cocina</h4>
                        </div>
                        @if($cocinaInsumos->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Valor</th>
                                    <th>Cantidad Actual</th>
                                    <th>Sector</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cocinaInsumos->sortBy('nombre') as $insumo)
                                <tr @if ($insumo->cantidad <= $insumo->stock_critico)
                                    style="background-color: red; color:white;"
                                @endif>
                                    <td>{{ $insumo->nombre }}</td>
                                    <td>{{ $insumo->valor }}</td>
                                    <td>{{ $insumo->cantidad }}
                                        @if ($insumo->cantidad <= 1) {{$insumo->unidadMedida->abreviatura}}.
                                            @else

                                            {{$insumo->unidadMedida->abreviatura}}s.
                                            @endif</td>
                                    <td>{{ $insumo->sector->nombre }}</td>
                                    <td>
                                        <a href="{{-- route('backoffice.unidad_medida.edit', $unidad->id) --}}"  class="tooltipped" data-tooltip="Editar" data-position="bottom">
                                            <i class="material-icons">mode_edit</i>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" style="color: purple" class="tooltipped" data-tooltip="Eliminar" data-position="bottom"
                                            onclick="enviar_formulario('{{-- route('backoffice.complemento.destroy', $unidad->id) --}}', 'unidades_medidas')">
                                            <i class="material-icons">delete</i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <h5 class="center">No existen registros</h5>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Barra --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Barra</h4>
                        </div>
                        @if($barraInsumos->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Valor</th>
                                    <th>Cantidad Actual</th>
                                    <th>Sector</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barraInsumos->sortBy('nombre') as $insumo)
                                <tr @if ($insumo->cantidad <= $insumo->stock_critico)
                                    style="background-color: red; color:white;"
                                @endif>
                                    <td>{{ $insumo->nombre }}</td>
                                    <td>{{ $insumo->valor }}</td>
                                    <td>{{ $insumo->cantidad }}
                                        @if ($insumo->cantidad <= 1) 
                                            {{$insumo->unidadMedida->abreviatura}}.
                                        @else
                                            {{$insumo->unidadMedida->abreviatura}}s.
                                        @endif</td>
                                    <td>{{ $insumo->sector->nombre }}</td>
                                    <td>
                                        <a href="{{-- route('backoffice.unidad_medida.edit', $unidad->id) --}}"  class="tooltipped" data-tooltip="Editar" data-position="bottom">
                                            <i class="material-icons">mode_edit</i>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" style="color: purple" class="tooltipped" data-tooltip="Eliminar" data-position="bottom"
                                            onclick="enviar_formulario('{{-- route('backoffice.complemento.destroy', $unidad->id) --}}', 'unidades_medidas')">
                                            <i class="material-icons">delete</i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <h5 class="center">No existen registros</h5>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Baños --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Baños</h4>
                        </div>
                        @if($banoInsumos->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Valor</th>
                                    <th>Cantidad Actual</th>
                                    <th>Sector</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($banoInsumos as $insumo)
                                <tr @if ($insumo->cantidad <= $insumo->stock_critico)
                                    style="background-color: red; color:white;"
                                @endif>
                                    <td>{{ $insumo->nombre }}</td>
                                    <td>{{ $insumo->valor }}</td>
                                    <td>{{ $insumo->cantidad }}
                                        @if ($insumo->cantidad <= 1) {{$insumo->unidadMedida->abreviatura}}.
                                            @else

                                            {{$insumo->unidadMedida->abreviatura}}s.
                                            @endif</td>
                                    <td>{{ $insumo->sector->nombre }}</td>
                                    <td>
                                        <a href="{{-- route('backoffice.unidad_medida.edit', $unidad->id) --}}"  class="tooltipped" data-tooltip="Editar" data-position="bottom">
                                            <i class="material-icons">mode_edit</i>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" style="color: purple" class="tooltipped" data-tooltip="Eliminar" data-position="bottom"
                                            onclick="enviar_formulario('{{-- route('backoffice.complemento.destroy', $unidad->id) --}}', 'unidades_medidas')">
                                            <i class="material-icons">delete</i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <h5 class="center">No existen registros</h5>
                        @endif

                    </div>
                </div>
            </div>


            {{-- Masajes --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Masajes</h4>
                        </div>
                        @if($masajeInsumos->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Valor</th>
                                    <th>Cantidad Actual</th>
                                    <th>Sector</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($masajeInsumos as $insumo)
                                <tr @if ($insumo->cantidad <= $insumo->stock_critico)
                                    style="background-color: red; color:white;"
                                @endif>
                                    <td>{{ $insumo->nombre }}</td>
                                    <td>{{ $insumo->valor }}</td>
                                    <td>{{ $insumo->cantidad }}
                                        @if ($insumo->cantidad <= 1) {{$insumo->unidadMedida->abreviatura}}.
                                            @else

                                            {{$insumo->unidadMedida->abreviatura}}s.
                                            @endif</td>
                                    <td>{{ $insumo->sector->nombre }}</td>
                                    <td>
                                        <a href="{{-- route('backoffice.unidad_medida.edit', $unidad->id) --}}"  class="tooltipped" data-tooltip="Editar" data-position="bottom">
                                            <i class="material-icons">mode_edit</i>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" style="color: purple" class="tooltipped" data-tooltip="Eliminar" data-position="bottom"
                                            onclick="enviar_formulario('{{-- route('backoffice.complemento.destroy', $unidad->id) --}}', 'unidades_medidas')">
                                            <i class="material-icons">delete</i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <h5 class="center">No existen registros</h5>
                        @endif

                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

@endsection

@section('foot')
<script>

    $(document).ready(function(){
        $('.tooltipped').tooltip();
      });
    </script>
@endsection