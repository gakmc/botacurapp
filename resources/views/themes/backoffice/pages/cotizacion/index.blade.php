@extends('themes.backoffice.layouts.admin')

@section('title','Cotizaciones')

@section('head')
@endsection

@section('breadcrumbs')
@endsection


@section('dropdown_settings')
<li>
    <a href="{{route('backoffice.cotizacion.create')}}">Generar Cotización</a>
</li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Cotizaciones</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <div class="row">


                            
                            <table class="highlight bordered">
                                <thead>
                                <tr>
                                    <th>Cotizacion</th>
                                    <th>Emitida</th>
                                    <th>Cliente</th>
                                    <th>Solicitante</th>
                                    <th>Correo</th>
                                    <th>Acciones</th>

                                </tr>
                                </thead>

                                <tbody>
                                    
                            @if ($cotizaciones->isNotEmpty())
                                @foreach ($cotizaciones as $cotizacion)
                                    <tr>
                                        <td>N.° {{$cotizacion->id}}</td>
                                        <td>{{$cotizacion->fecha_emision->format('d-m-Y')}}</td>
                                        <td>{{$cotizacion->cliente}}</td>
                                        <td>{{$cotizacion->solicitante}}</td>
                                        <td>{{$cotizacion->correo}}</td>
                                        <td>
                                            <a href="{{route('backoffice.cotizacion.show', $cotizacion)}}"><i class='material-icons'>remove_red_eye</i></a>
                                            <a href="{{route('backoffice.cotizacion.edit', $cotizacion)}}"><i class='material-icons purple-text'>edit</i></a>
                                            <a href="#" onclick="enviar_formulario({{$cotizacion->id}})"><i class='material-icons red-text'>delete</i></a>

                                        </td>
                                    </tr>
                                    
                                    <form method="post" action="{{route('backoffice.cotizacion.destroy', $cotizacion) }}" name="delete_cotizacion" id="delete-cotizacion-{{$cotizacion->id}}">
                                        {{csrf_field()}}
                                        {{method_field('DELETE')}}
                                    </form>
                                @endforeach

                            @else
                                <tr>
                                    <td colspan="3">No se registran cotizaciones</td>
                                </tr>
                            @endif
                                </tbody>
                            </table>


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
 function enviar_formulario(id)
 {
     Swal.fire({
         title: "¿Deseas eliminar esta cotización?",
         text: "Esta acción no se puede revertir.",
         type: "warning",
         icon: "warning",
         showCancelButton: true,
         confirmButtonText: "Si, continuar",
         cancelButtonText: "No, cancelar",
         closeOnCancel: false,
         closeOnConfirm: true
     }).then((result)=> {
         if(result.value){
             document.getElementById('delete-cotizacion-'+id).submit();
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
@endsection
