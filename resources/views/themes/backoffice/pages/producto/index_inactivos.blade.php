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
    <div class="row right">
        <div class="col s12">
            <a href="{{ route('backoffice.producto.index') }}"
            class="btn {{ request()->routeIs('backoffice.producto.index') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
            Activos
            </a>
            <a href="{{ route('backoffice.producto.inactivos') }}"
            class="btn {{ request()->routeIs('backoffice.producto.inactivos') ? 'pink-text text-darken-2' : '' }}" style="background-color: #039B7B">
            Inactivos
            </a>
        </div>
    </div>
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

                                    <td>
                                        @if($producto->estado === 'inactivo' || $producto->estado === null)
                                        <button class="btn-small waves-effect cambiar-estado tooltipped" data-position="top" data-delay="50" data-tooltip="Activar"
                                                data-id="{{ $producto->id }}"
                                                data-estado="activo"
                                                data-action="{{ route('backoffice.producto.estado', $producto) }}">
                                        <i class="material-icons">done_all</i>
                                        </button>

                                        @endif
                                    </td>

                                    <td><a class="btn-small cyan" href="{{ route('backoffice.producto.edit', $producto )}}"><i class="material-icons">mode_edit</i></a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @else
                            <h5>No se registran productos inactivos</h5>
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
// CSRF para jQuery (Laravel 6)
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
});

$(document).on('click', '.cambiar-estado', function(e){
  e.preventDefault();

  const $btn   = $(this);
  const id     = $btn.data('id');
  const estado = $btn.data('estado');
  const url    = $btn.data('action'); // ya viene con el prefix backoffice

  $.ajax({
    url: url,
    type: 'PATCH',
    data: { estado: estado },
    success: function(res){
      // Quita la fila (si estás en vista de activos)
      $btn.closest('tr').fadeOut(250, function(){ $(this).remove(); });

      // Toast (elige uno: Materialize o SweetAlert)
      if (window.M) M.toast({ html: res.msg ?? 'Estado actualizado', classes: 'green' });
      if (window.Swal) Swal.fire({ toast:true, position:'center', icon:'success',
        title: res.msg ?? 'Estado actualizado', showConfirmButton:false, timer:4000 });
    },
    error: function(xhr){
      const msg = xhr?.responseJSON?.message || 'Error al cambiar estado';
      if (window.M) M.toast({ html: msg, classes:'red' });
      if (window.Swal) Swal.fire({ toast:true, position:'center', icon:'error',
        title: msg, showConfirmButton:false, timer:4000 });
    }
  });
});
</script>
<script>
    @if(session('status'))
        Swal.fire({
            toast: true,
            position: '',
            icon: 'success',
            title: '{{ session('status') }}',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        @endif
</script>

<script>
    $(document).ready(function(){
        $('.tooltipped').tooltip({delay: 50});
    });
</script>
@endsection