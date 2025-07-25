@extends('themes.backoffice.layouts.admin')

@section('title', 'Gift Cards')

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.giftcards.create') }}" class="grey-text text-darken-2">Crear Gift Card</a></li>
@endsection

@section('content')
<div class="section">
    
    <div class="right-align">
        @if ($mostrarUsadas)
        <a href="{{ route('backoffice.giftcards.index') }}" class="btn green darken-1">Ver sin usar</a>
        @else
        <a href="{{ route('backoffice.giftcards.index', ['usadas' => 1]) }}" class="btn grey darken-2">Ver usadas</a>
        @endif
    </div>
    
    <p class="caption left-align"><strong>Gift Cards {{ $mostrarUsadas ? 'Usadas' : 'Sin Usar' }}</strong></p>

    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <div class="row">


                        @if ($giftcards->isEmpty())
                            <h5 class="center">No hay gift cards {{ $mostrarUsadas ? 'usadas' : 'sin usar' }}</h5>
                        @else
                            <table class="highlight">
                                <thead>
                                    <tr>
                                        <th>Solicitada</th>
                                        <th>Código</th>
                                        <th>Monto</th>
                                        <th>Usada</th>
                                        <th>Valido hasta</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($giftcards as $gc)
                                    <tr>
                                        <td>{{ $gc->de }}</td>
                                        <td>{{ $gc->codigo }}</td>
                                        <td>${{ number_format($gc->monto, 0, ',', '.') }}</td>
                                        <td>{{ $gc->usada ? 'Sí' : 'No' }}</td>
                                        <td>{{ $gc->valido }}</td>
                                        <td>
                                            <a class="btn-small btn-floating blue" href="{{route('backoffice.giftcards.show', $gc)}}"><i class='material-icons'>remove_red_eye</i></a>

                                            <a class="btn-small btn-floating purple" href="{{route('backoffice.giftcards.edit', $gc)}}"><i class='material-icons'>edit</i></a>


                                            <a onclick="enviar_formulario({{$gc->id}})" class="btn-small btn-floating red" href="#"><i class='material-icons'>delete</i></a>

                                            <form method="post" action="{{route('backoffice.giftcards.destroy', $gc->id) }} " name="delete_form_{{$gc->id}}">
                                                {{csrf_field()}}
                                                {{method_field('DELETE')}}
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
            title: "¿Deseas eliminar esta Gift Card?",
            text: "Esta acción no se puede deshacer",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Si, continuar",
            cancelButtonText: "No, cancelar",
            closeOnCancel: false,
            closeOnConfirm: true
        }).then((result)=> {
            if(result.value){
                document.forms['delete_form_'+id].submit();
            }else{
                Swal.fire(
                    'Operación Cancelada',
                    'La gift card no fue eliminada',
                    'error'
                )
            }
        });
    }
</script>
@endsection