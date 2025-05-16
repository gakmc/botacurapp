@extends('themes.backoffice.layouts.admin')

@section('title', 'Productos Poro Poro')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="">Venta directa</a></li> --}}
@endsection

@section('dropdown_settings')
{{-- Opciones adicionales aquí --}}
<li><a href="{{route("backoffice.poroporo.create")}}">Ingresar Producto</a></li>
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Productos Poro Poro</strong></p> 
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">

                    <table>
                        <thead>
                            <tr>
                                <th data-field="nombre">Nombre</th>
                                <th data-field="valor">Valor</th>
                                <th data-field="descripcion">Descripción</th>
                                <th data-field="acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($productos->isNotEmpty())
                                @foreach ($productos as $producto)
                                    <tr>
                                        <td>{{ $producto->nombre }}</td>
                                        
                                        <td>${{ number_format($producto->valor,0,'','.') }}</td>
                                        <td>{{ $producto->descripcion }}</td>
                                        <td>
                                            {{-- <a href="#modal{{$producto->id }}" class="btn-floating btn-small waves-effect waves-light blue modal-trigger"><i class="material-icons">visibility</i></a> --}}


                                            @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')) )
                                            
                                                <a href="{{ route('backoffice.poroporo.edit', $producto) }}" class="btn-floating btn-small waves-effect waves-light purple"><i class="material-icons">edit</i></a>

                                                <a href="#" class="btn-floating btn-small waves-effect waves-light red btn-eliminar-poroporo" data-url="{{ route('backoffice.poroporo.destroy', ['poroporo' => $producto->id]) }}"><i class="material-icons">delete</i></a>
                                                
                                            @endif

                                        </td>
                                        
                                    </tr>
                                    {{-- @include('themes.backoffice.pages.poroporo.includes.modal_venta', ['producto' => $producto]) --}}

                                @endforeach
                            @else
                            <tr>
                                <td colspan="2"></td>
                                <td><h5><strong>No hay registro de Poro Poro</strong></h5></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>

                    <form id="form-eliminar-poroporo" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    
                </div>
            </div>

            {{-- <div class="col s12 m4">
                @include('themes.backoffice.pages.poroporo.includes.poro_nav', $productos)
            </div> --}}
        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    $(document).ready(function() {
        $('select').material_select();
        $('.modal').modal();
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-eliminar-poroporo').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.dataset.url;
    
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede deshacer",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form-eliminar-poroporo');
                        form.setAttribute('action', url);
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection
