@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{route ('backoffice.reserva.listar') }}" class="grey-text text-darken-2">Todas las Reservas</a></li> --}}
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
    <a href="?page=1"><p class="caption"><strong>Reservas de hoy: {{ now()->format('d-m-Y') }}</strong></p></a>

    {{-- <div class="row">
        <div class="col s2 green-text offset-s2">
            <i class='material-icons left'>done_all</i>Registro completo
        </div>
        <div class="col s2 green-text offset-s2">
            <i class='material-icons left'>check</i>Registro incompleto
        </div>
        <div class="col s2 red-text offset-s1">
            <i class='material-icons left'>close</i>No Registra
        </div> 
    </div> --}}
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">


            @if (!isset($reservas))
                <h5 class="center">
                    No se registran ventas
                </h5>
            @else

                    <table class="bordered">
                        <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>WhatsApp</th>
                            <th>Ubicacion</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                
                        <tbody>

                        @foreach ($reservas as $reserva)
                        @php
                            $visita   = $reserva->visitas->last();
                            $visitas  = $reserva->visitas;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{route('backoffice.reserva.show', $reserva)}}">

                                    {{$reserva->cliente->nombre_cliente}}
                                </a>
                            </td>
                            <td>
                                @if(is_null($reserva->cliente->whatsapp_cliente)) 
                                    No Registra
                                @else
                                    <a href="https://api.whatsapp.com/send?phone={{$reserva->cliente->whatsapp_cliente}}" target="_blank">+{{$reserva->cliente->whatsapp_cliente}}</a>
                                @endif
                            </td>
                            <td>
                                {{$visita->ubicacion->nombre}}
                            </td>
                            <td>
                            @if (is_null($reserva->venta->diferencia_programa))
                                <a href="{{route('backoffice.reserva.venta.cerrar', ['reserva'=>$reserva, 'ventum'=>$reserva->venta]) }}"
                                    class="collection-item center-align valign-wrapper left">
                                    <i class='material-icons tooltipped' data-position="bottom" data-tooltip="Ver Consumo">remove_red_eye</i>
                                </a>

                                <a href="{{route('backoffice.reserva.venta.cerrar', ['reserva'=>$reserva, 'ventum'=>$reserva->venta]) }}"
                                    class="collection-item center-align valign-wrapper left">
                                    <i class='material-icons tooltipped red-text' data-position="bottom" data-tooltip="Cerrar Venta">attach_money</i>
                                </a>
                    
                            @else
                    
                                <a class="collection-item center-align valign-wrapper left" href="{{ route('backoffice.venta.pdf', $reserva) }}"
                                    target="_blank">
                                    <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF Venta">picture_as_pdf</i>
                                </a>
                            @endif
                            </td>
                        </tr>
                            
                        @endforeach
                        


                        </tbody>
                    </table>


            @endif

        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    // function activar_alerta(cliente)
    // {
    //     console.log(cliente);
        
    //     Swal.fire({
    //         toast: true,
    //         icon: 'warning',
    //         title: `${cliente} no registra masajes`,
    //         color: 'white',
    //         iconColor: 'white',
    //         background: "#039B7B",
    //         showConfirmButton: false,
    //         timer: 5000,
    //         timerProgressBar: true,
    //             didOpen: (toast) => {
    //             toast.onmouseenter = Swal.stopTimer;
    //             toast.onmouseleave = Swal.resumeTimer;
    //             }
    //     });
    // }
   </script>
@endsection