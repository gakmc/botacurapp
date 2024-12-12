@extends('themes.backoffice.layouts.admin')

@section('title', 'Permisos del Sistema')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.permission.index')}}">Permisos del Sistema</a></li>
@endsection

@section('dropdown_settings')
<li><a href="{{route('backoffice.permission.create')}}" class="grey-text text-darken-2">Crear Permiso</a></li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Permisos del Sistema</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <div class="row">





                        <div class="card">
                            <div class="card-content gradient-45deg-light-blue-cyan">
                                <h5 class="white-text" id="nombreSeleccion">nombre permiso</h5>
                            </div>

                            <div class="card-tabs">
                                <ul class="tabs tabs-fixed-width">
                                    @foreach($roles as $role)
                                    <li class="tab"><a href="#role_{{$role->id}}" id="seleccion">{{$role->name}}</a>
                                    </li>

                                    @endforeach
                                </ul>
                            </div>

                            <div class="card-content grey lighten-4">
                                @foreach($roles as $role)
                                <div id="role_{{$role->id}}" class="tipo-section">

                                    <table class="striped">
                                        <thead>
                                            <tr>
                                                <th>Nombre del Permiso</th>
                                                <th>Descripción</th>
                                                <th>Slug</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($role->permissions->isEmpty())

                                            <tr>
                                                <td>El rol <strong>{{$role->name}}</strong> No registra permisos</td>
                                            </tr>


                                            @else

                                            @foreach($role->permissions as $permission)
                                            <tr>
                                                <td><a
                                                        href="{{route ('backoffice.permission.show', $permission)}}">{{$permission->name}}</a>
                                                </td>
                                                <td>{{ $permission->description }}</td>
                                                <td>{{ $permission->slug }}</td>
                                                <td><a
                                                        href="{{ route('backoffice.permission.edit', $permission) }}">Editar</a>
                                                </td>
                                            </tr>
                                            @endforeach

                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                @endforeach
                            </div>
                        </div>









                        {{-- <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th>Descripción</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($permissions as $permission)
                                <tr>
                                    <td><a
                                            href="{{route ('backoffice.permission.show', $permission)}}">{{$permission->name}}</a>
                                    </td>
                                    <td>{{$permission->slug}}</td>
                                    <td>{{$permission->description}}</td>
                                    <td><a
                                            href="{{route ('backoffice.role.show', $permission->role)}}">{{$permission->role->name}}</a>
                                    </td>
                                    <td><a href="{{route ('backoffice.permission.edit', $permission)}}">Editar</a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table> --}}

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
            $('#nombreSeleccion').text('Permisos para el rol: '+nombreInicial); // Mostrar el nombre inicial

            // Escuchar el evento click en las pestañas
            $('.tabs .tab a').on('click', function (e) {
                e.preventDefault();

                // Obtener el texto del enlace seleccionado
                let nombreTipo = $(this).text();

                // Actualizar el contenido del h5 con id "nombreSeleccion"
                $('#nombreSeleccion').text('Permisos para el rol: '+nombreTipo);
            });
    });
</script>
@endsection