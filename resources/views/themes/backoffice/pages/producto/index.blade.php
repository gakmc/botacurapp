@extends('themes.backoffice.layouts.admin')

@section('title','Productos')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.producto.index')}}">Productos</a></li>
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.producto.create') }}" class="grey-text text-darken-2">Crear Producto</a></li>
<!-- <li><a href="" class="grey-text text-darken-2">Crear Usuario</a></li> -->
@endsection


@section('content')

<div class="section">
    <p class="caption"><strong>Productos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">

                    <div class="row">

                        @if ($productos->isNotEmpty())


                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Valor</th>
                                    <th>Tipo de Producto</th>
                                    
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productos->sortBy('nombre')->sortBy('id_tipo_producto') as $producto )
                                <tr>
                                    <td><a
                                            href="{{route('backoffice.producto.show' ,$producto )}}">{{$producto->nombre}}</a>
                                    </td>
                                    <td>{{'$'.number_format($producto->valor, 0, '', '.')}}</td>
                                    <td>{{$producto->tipoProducto->nombre}}</td>

                                    <td><a href="{{ route('backoffice.producto.edit', $producto )}}"><i
                                                class="material-icons">mode_edit</i> Editar</a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @else
                            <h5>No se registran productos</h5>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')
@endsection