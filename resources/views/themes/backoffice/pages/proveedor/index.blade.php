@extends('themes.backoffice.layouts.admin')

@section('title', 'Lista de Proveedores')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos</a></li>
<li>Lista de Proveedores</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.proveedor.create') }}" class="grey-text text-darken-2">Crear Proveedor</a></li>
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Proveedores</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">


                        <table class="centered">
                            <thead>
                                <tr>
                                    <th data-field="nombre">Nombre</th>
                                    <th data-field="rut">Rut</th>
                                    <th data-field="telefono">Teléfono</th>
                                    <th data-field="correo">Correo Electrónico</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                    @if ($proveedores->isNotEmpty())
                                    @foreach ($proveedores as $proveedor)

                                    <tr>
                                        <td>{{$proveedor->nombre}}</td>
                                        <td>{{$proveedor->rut ?? '-'}}</td>
                                        <td>{{$proveedor->telefono ?? '-'}}</td>
                                        <td>{{$proveedor->correo ?? '-'}}</td>
                                        <td>
                                            <a href="{{route('backoffice.proveedor.edit', $proveedor)}}" class="btn-small btn-floating purple"><i class='material-icons'>edit</i></a>
                                            <form id="form-eliminar-{{ $proveedor->id }}" action="{{ route('backoffice.proveedor.destroy', $proveedor->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn-floating btn-small red" onclick="confirmarEliminacion({{ $proveedor->id }})">
                                                    <i class='material-icons'>delete</i>
                                                </button>
                                            </form>

                                        </td>
                                    </tr>
                                        
                                    @endforeach
                                    @else
                                    <tr>
                                        <td><h5>No se registran proveedores</h5></td>
                                    </tr>
                                        
                                    @endif
                                </tbody>
                            </table>
                        {{-- CONTENIDO --}}



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
        @if(session('info'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

        @if(session('success'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif
    });
</script>

<script>
    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar este proveedor?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-eliminar-' + id).submit();
            }
        });
    }
</script>
@endsection