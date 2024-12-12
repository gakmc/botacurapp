@extends('themes.backoffice.layouts.admin')

@section('title', '')

@section('head')
@endsection


@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create') }}" class="grey-text text-darken-2">Crear Reserva</a></li> --}}
<li><a href="{{route ('backoffice.sectores.create') }}" class="grey-text text-darken-2">Crear Sector</a></li>
<li><a href="{{route ('backoffice.ubicaciones.create') }}" class="grey-text text-darken-2">Crear Ubicacion</a></li>
<li><a href="{{route ('backoffice.unidades_medidas.create') }}" class="grey-text text-darken-2">Crear Unidad de
        Medida</a></li>
<li><a href="{{route ('backoffice.tipo_documentos.create') }}" class="grey-text text-darken-2">Crear Tipo de
        Documento</a></li>
<li><a href="{{route ('backoffice.tipo_productos.create') }}" class="grey-text text-darken-2">Crear Tipo de
        Producto</a></li>
<li><a href="{{route ('backoffice.tipo_transacciones.create') }}" class="grey-text text-darken-2">Crear Tipo de
        Transacción</a></li>
<li><a href="{{route ('backoffice.categoria_compras.create') }}" class="grey-text text-darken-2">Crear Categoria
        Compra</a></li>
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Complementos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">

            {{-- SECTORES --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        {{-- CONTENIDO --}}
                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Sectores</h4>
                        </div>
                        @if($sectores->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sectores as $sector)
                                <tr>
                                    <td>

                                        {{ $sector->nombre }}

                                    </td>
                                    <td>
                                        <a href="{{ route('backoffice.sector.edit', $sector->id) }}">
                                            <i class="material-icons">mode_edit</i> Editar
                                        </a>


                                    </td>

                                    <td>

                                        <a href="#" style="color: red"
                                            onclick="enviar_formulario('{{ route('backoffice.complemento.destroy', $sector->id) }}', 'sectores')">
                                            <i class="material-icons">delete</i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <h5>No existen registros</h5>
                        @endif




                    </div>
                </div>
            </div>

            {{-- UBICACIONES --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        {{-- CONTENIDO --}}
                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Ubicaciones</h4>
                        </div>
                        @if($ubicaciones->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ubicaciones as $ubicacion)
                                <tr>
                                    <td>

                                        {{ $ubicacion->nombre }}

                                    </td>
                                    <td>
                                        <a href="{{ route('backoffice.ubicacion.edit', $ubicacion->id) }}">
                                            <i class="material-icons">mode_edit</i> Editar
                                        </a>
                                    </td>
                                    <td>

                                        <a href="#" style="color: red"
                                            onclick="enviar_formulario('{{ route('backoffice.complemento.destroy', $ubicacion->id) }}', 'ubicaciones')">
                                            <i class="material-icons">delete</i> Eliminar
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
        <div class="row">

            {{-- TIPOS DE DOCUMENTOS --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        {{-- CONTENIDO --}}
                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Tipos de Documentos</h4>
                        </div>
                        @if($documentos->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documentos as $documento)
                                <tr>
                                    <td>

                                        {{ $documento->nombre }}

                                    </td>
                                    <td>
                                        @if (is_null($documento->descripcion))
                                        No Registra
                                        @else
                                        {{ $documento->descripcion }}
                                        @endif

                                    </td>
                                    <td>
                                        <a href="{{ route('backoffice.tipo_documento.edit', $documento->id) }}">
                                            <i class="material-icons">mode_edit</i> Editar
                                        </a>
                                    </td>

                                    <td>
                                        <a href="#" style="color: red"
                                            onclick="enviar_formulario('{{ route('backoffice.complemento.destroy', $documento->id) }}', 'tipos_documentos')">
                                            <i class="material-icons">delete</i> Eliminar
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


            {{-- TIPOS DE TRANSACCIONES --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        {{-- CONTENIDO --}}
                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Tipos de Transacciones</h4>
                        </div>
                        @if($transacciones->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transacciones as $transaccion)
                                <tr>
                                    <td>

                                        {{ $transaccion->nombre }}

                                    </td>
                                    <td>
                                        <a href="{{ route('backoffice.tipo_transaccion.edit', $transaccion->id) }}">
                                            <i class="material-icons">mode_edit</i> Editar
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" style="color: red"
                                            onclick="enviar_formulario('{{ route('backoffice.complemento.destroy', $transaccion->id) }}', 'tipos_transacciones')">
                                            <i class="material-icons">delete</i> Eliminar
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

        <div class="row">


            {{-- CATEGORIAS COMPRAS --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        {{-- CONTENIDO --}}
                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Categoria Compras</h4>
                        </div>
                        @if($categorias->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categorias as $categoria)
                                <tr>
                                    <td>

                                        {{ $categoria->nombre }}

                                    </td>
                                    <td>
                                        <a href="{{ route('backoffice.categoria_compras.edit', $categoria->id) }}">
                                            <i class="material-icons">mode_edit</i> Editar
                                        </a>
                                    </td>

                                    <td>

                                        <a href="#" style="color: red"
                                            onclick="enviar_formulario('{{ route('backoffice.complemento.destroy', $categoria->id) }}', 'categoria_compras')">
                                            <i class="material-icons">delete</i> Eliminar
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

            {{-- UNIDADES DE MEDIDA --}}
            <div class="col s12 m6">
                <div class="card-panel">
                    <div class="row">

                        {{-- CONTENIDO --}}
                        <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                            <h4>Tipos de Productos</h4>
                        </div>
                        @if($tipoProductos->isNotEmpty())
                        <table class="centered responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Sector</th>

                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tipoProductos as $tipoProducto)
                                <tr>
                                    <td>

                                        {{ $tipoProducto->nombre }}

                                    </td>
                                    <td>

                                        {{ $tipoProducto->sector->nombre }}.

                                    </td>
                                    <td>
                                        <a href="{{ route('backoffice.tipo_producto.edit', $tipoProducto->id) }}">
                                            <i class="material-icons">mode_edit</i> Editar
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" style="color: red"
                                            onclick="enviar_formulario('{{ route('backoffice.complemento.destroy', $tipoProducto->id) }}', 'productos')">
                                            <i class="material-icons">delete</i> Eliminar
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


        <div class="row">
                        {{-- UNIDADES DE MEDIDA --}}
                        <div class="col s12 m6">
                            <div class="card-panel">
                                <div class="row">
            
                                    {{-- CONTENIDO --}}
                                    <div class="card-panel gradient-45deg-light-blue-cyan white-text center">
                                        <h4>Unidades de Medida</h4>
                                    </div>
                                    @if($unidades->isNotEmpty())
                                    <table class="centered responsive-table">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Abreviatura</th>
                                                <th>Tipo</th>
                                                <th>Descripcion</th>
                                                <th colspan="2">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($unidades as $unidad)
                                            <tr>
                                                <td>
            
                                                    {{ $unidad->nombre }}
            
                                                </td>
                                                <td>
            
                                                    {{ $unidad->abreviatura }}.
            
                                                </td>
                                                <td>
            
                                                    {{ ucwords($unidad->tipo) }}
            
                                                </td>
                                                <td>
                                                    @if (is_null($unidad->descripcion))
                                                    No Registra
                                                    @else
                                                    {{ $unidad->descripcion }}
                                                    @endif
            
                                                </td>
                                                <td>
                                                    <a href="{{ route('backoffice.unidad_medida.edit', $unidad->id) }}">
                                                        <i class="material-icons">mode_edit</i> Editar
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="#" style="color: red"
                                                        onclick="enviar_formulario('{{ route('backoffice.complemento.destroy', $unidad->id) }}', 'unidades_medidas')">
                                                        <i class="material-icons">delete</i> Eliminar
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
</div>
<form id="delete_form" method="post" action="">
    {{ csrf_field() }}
    {{ method_field('DELETE') }}
    <input type="hidden" name="table" id="table_name">
</form>
@endsection

@section('foot')
<script>
    function enviar_formulario(actionUrl, table) {
    const form = document.getElementById('delete_form');
    form.action = actionUrl;
    document.getElementById('table_name').value = table; // Asegúrate de que este valor sea correcto

    Swal.fire({
        title: "¿Deseas eliminar este registro?",
        text: "Esta acción no se puede deshacer",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, continuar",
        cancelButtonText: "No, cancelar",
        reverseButtons: true 
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit(); 
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire('Operación Cancelada', 'Registro no eliminado', 'error');
        }
    });
}
</script>
@endsection