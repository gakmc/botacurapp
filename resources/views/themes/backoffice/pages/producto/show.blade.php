@extends('themes.backoffice.layouts.admin')

@section('title', $producto->nombre)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.producto.index')}}">Productos</a></li>
<li>{{$producto->nombre}}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.producto.create') }}" class="grey-text text-darken-2">Crear Producto</a>
</li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Producto:</strong> {{$producto->nombre}}</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">



                        <span class="card-title activator grey-text text-darken-4">{{$producto->nombre}} - ${{$producto->valor}}</span>
                        <p>
                            @if (is_null($producto->insumos))
                            <i class="material-icons">check</i> No Registra
                            @else
                            @foreach ($producto->insumos as $insumo)

                        <p>
                            <i class="material-icons">check</i>

                            <strong>{{$insumo->nombre}}</strong>  <p>
                            <strong>Cantidad a usar:</strong>    {{$insumo->pivot->cantidad_insumo_usar}}
                            {{$insumo->nombreUnidadMedida()}}


                            </p>
                        </p>

                        @endforeach
                        @endif
                    </p>
                    
                    <h5><strong>Total Costo de Producto:</strong> {{$totalCostoProducto}}</h5>




                    </div>
                    <div class="card-action">
                        <a href="{{route('backoffice.producto.edit', $producto) }}" class="purple-text">Editar</a>
                        {{-- <a href="#" style="color: red" onclick="enviar_formulario()">Eliminar</a> --}}
                    </div>
                </div>
            </div>






        </div>
    </div>
</div>
</div>

<form method="post" action="{{route('backoffice.producto.destroy', $producto) }} " name="delete_form">
    {{csrf_field()}}
    {{method_field('DELETE')}}
</form>
@endsection

@section('foot')
<script>
    function enviar_formulario()
 {
     Swal.fire({
         title: "¿Deseas eliminar este producto?",
         text: "Esta acción no se puede deshacer",
         type: "warning",
         showCancelButton: true,
         confirmButtonText: "Si, continuar",
         cancelButtonText: "No, cancelar",
         closeOnCancel: false,
         closeOnConfirm: true
     }).then((result)=> {
         if(result.value){
             document.delete_form.submit();
         }else{
             Swal.fire(
                 'Operación Cancelada',
                 'Registro no eliminado',
                 'error'
             )
         }
     });
 }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var elems = document.querySelectorAll('.modal');
        M.Modal.init(elems);
    });
</script>

@endsection