@extends('themes.backoffice.layouts.admin')

@section('title', 'Gestión Consumo')

@section('breadcrumbs')
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{route ('backoffice.reserva.listar') }}" class="grey-text text-darken-2">Todas las Reservas</a></li> --}}
@endsection

@section('head')
<style>
    .dropdown-content {
        position: absolute !important;
        top: auto !important;
        width: auto !important;
        min-width: 150px !important;
        max-width: 250px !important;
    }

    /* Centrar el botón del dropdown */
    td .dropdown-button {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
    }
</style>
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

        <div class="col s12" data-tipo="sauna">
            <div class="card-panel animate__animated animate__fadeInRight"
                style="--animate-delay: 1s; --animate-duration: 2s;">       


            @if (!isset($reservas))
                <h5 class="center">
                    No se registran ventas
                </h5>
            @else

                    <table class="">
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
                            $consumo = $reserva->venta->consumo;
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
                                @if (!is_null($visita->id_ubicacion))
                                    {{$visita->ubicacion->nombre}}
                                @else
                                    <a id="noRegistra" href="{{route('backoffice.visita.edit_ubicacion',['visitum'=>$reserva->visitas->first()])}}">No registra ubicación.</a>
                                @endif
                            </td>
                            <td>
                                <a class='dropdown-ventas btn-flat' href='#' data-activates='dropdown-{{$reserva->id}}'><i class='material-icons'>more_vert</i></a>


                                <ul id='dropdown-{{$reserva->id}}' class='dropdown-content'>
                                    @if (is_null($reserva->venta->diferencia_programa))
                                        @if(!empty($consumo))
                                            
                                                {{-- <td>
                                                    <a href="#modal-{{$reserva->id}}"
                                                        class="collection-item center-align valign-wrapper left modal-trigger"><i class='material-icons left blue-text' data-position="bottom" data-tooltip="Ver Consumo">remove_red_eye</i>Ver Consumo
                                                
                                                    </a>
                                                </td> --}}
        
                                                <li><a href="#modal-{{$reserva->id}}" class="modal-trigger"><i class="material-icons">remove_red_eye</i>Ver Consumo</a></li>
                                            
                                        @endif
                                        {{-- <td>
                                            <a 
                                                href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}"
                                                class="collection-item center-align valign-wrapper left"
                                                data-position="bottom" data-tooltip="Ingresar Consumo">
                                                    <i class="left material-icons pink-text ">local_bar</i>
                                                    Ingresar Consumo        
                                            </a>
                                        </td> --}}
        
                                        <li><a href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}"><i class="material-icons">local_bar</i>Ingresar Consumo</a></li>
        
                                        {{-- <td>    
                                            <a 
                                                href="{{route('backoffice.reserva.venta.cerrar', ['reserva'=>$reserva, 'ventum'=>$reserva->venta]) }}"
                                                class="collection-item center-align valign-wrapper left">
                                                    <i class='material-icons red-text left' data-position="bottom" data-tooltip="Cerrar Venta">attach_money</i>
                                            Cerrar Venta
                                            </a>
                                        </td> --}}
        
                                        <li><a href="{{route('backoffice.reserva.venta.cerrar', ['reserva'=>$reserva, 'ventum'=>$reserva->venta]) }}"><i class="material-icons">attach_money</i>Cerrar venta</a></li>
                            
                                    @else
                                        {{-- <td>
                                            <a class="collection-item center-align valign-wrapper left" href="{{ route('backoffice.venta.pdf', $reserva) }}"
                                                target="_blank">
                                                <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF Venta">picture_as_pdf</i>
                                            </a>
                                        </td> --}}
                                        <li><a href="{{ route('backoffice.venta.pdf', $reserva) }}" target="_blank"><i class="material-icons">picture_as_pdf</i>PDF venta</a></li>
                                    @endif
                                </ul>
                            </td>
                            
                        </tr>
                        @endforeach
                        

                        
                        
                        </tbody>
                    </table>
                

                
                
            @endif

                
            
        </div>
        @foreach ($reservas as $reserva)
            
            @include('themes.backoffice.pages.reserva.includes.modal_boleta', ['reserva' => $reserva])

        @endforeach
        </div>
    </div>
</div>
@endsection

@section('foot')
<script>

    $(document).ready(function () {
        $('.modal').modal();

        $('.dropdown-ventas').dropdown({
                alignment: 'right', // Alinea a la derecha
                constrainWidth: false, // No limita el ancho
                coverTrigger: false, // No cubre el botón
                hover: false, // Solo abre al hacer clic
                inDuration: 300, 
                outDuration: 200, 
                belowOrigin: true,
                gutter: 0, // Ajusta el espacio para evitar que se desplace
        });

    });



    // document.addEventListener('DOMContentLoaded', function() {
    //     var modals = document.querySelectorAll('.modal');
    //     M.Modal.init(modals);
    // });



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

<script>
    $(document).ready(function () {
        $('.collection-item').on('click', function (event) {
            let noRegistra = $(this).closest('tr').find('#noRegistra').length > 0;

            if (noRegistra) {
                event.preventDefault();
                
                Swal.fire({
                    toast: true,
                    icon: 'warning',
                    title: 'Debes agregar la ubicación antes de ingresar el consumo.',
                    color: 'white',
                    iconColor: 'yellow',
                    background: "#039B7B",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
            }
        });
    });
</script>
@endsection