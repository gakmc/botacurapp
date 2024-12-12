@extends('themes.backoffice.layouts.admin')

@section('title','Panel de Administración')

@section('head')
@endsection

@section('breadcrumbs')
<li>Panel de Administración</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Panel de Administración</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">



                        {{-- CONTENIDO --}}
                        <div id="card-stats">
                            <div class="row mt-1">
                                <!-- Tarjeta para mostrar el número de Reservas -->
                                <div class="col s12 m6 l3">
                                    <a href="{{route('backoffice.reserva.index')}}">
                                        <div class="animate__animated animate__backInLeft card gradient-45deg-light-blue-cyan gradient-shadow min-height-100 white-text"
                                            style="--animate-delay: 1s; --animate-duration: 2s; ">
                                            <div class="padding-4">
                                                <div class="col s7 m7">
                                                    <i class="material-icons background-round mt-5">assignment</i>
                                                    <p>Reservas</p>
                                                </div>
                                                <div class="col s5 m5 right-align">
                                                    <h5 id="reservas-count" class="mb-0">{{$totalReservas}}</h5>
                                                    <p class="no-margin">Total</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- Tarjeta para mostrar el número de Clientes -->
                                <div class="col s12 m6 l3">
                                    <a href="{{route('backoffice.cliente.index')}}">
                                        <div class="animate__animated animate__backInLeft card gradient-45deg-red-pink gradient-shadow min-height-100 white-text"
                                            style="--animate-delay: 1s; --animate-duration: 2s;">
                                            <div class="padding-4">
                                                <div class="col s7 m7">
                                                    <i class="material-icons background-round mt-5">airport_shuttle</i>
                                                    <p>Clientes</p>
                                                </div>
                                                <div class="col s5 m5 right-align">
                                                    <h5 id="clientes-count" class="mb-0">{{$totalClientes}}</h5>
                                                    <p class="no-margin">Total</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>



                                <!-- Tarjeta para mostrar el número de Masajes Asignados -->
                                <div class="col s12 m6 l3">
                                    <a href="{{route('backoffice.admin.index')}}">
                                        <div class="animate__animated animate__backInLeft card gradient-45deg-green-teal gradient-shadow min-height-100 white-text"
                                            style="--animate-delay: 1s; --animate-duration: 2s;">
                                            <div class="padding-4">
                                                <div class="col s7 m7">
                                                    <i class="material-icons background-round mt-5">spa</i>
                                                    <p>Masajes Asignados</p>
                                                </div>
                                                <div class="col s5 m5 right-align">
                                                    <h5 id="clientes-count" class="mb-0">{{$masajesAsignados}}</h5>
                                                    <p class="no-margin">Total</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>


                                <!-- Tarjeta para mostrar el número de Equipos de la semana -->
                                <div class="col s12 m6 l3">
                                    <a href="{{route('backoffice.admin.team')}}">
                                        <div class="animate__animated animate__backInLeft card gradient-45deg-indigo-light-blue gradient-shadow min-height-100 white-text"
                                            style="--animate-delay: 1s; --animate-duration: 2s;">
                                            <div class="padding-4">
                                                <div class="col s7 m7">
                                                    <i class="material-icons background-round mt-5">people</i>
                                                    <p>Equipos de la semana</p>
                                                </div>
                                                <div class="col s5 m5 right-align">
                                                    <h5 id="clientes-count" class="mb-0">{{$asignacionesSemanaActual}}</h5>
                                                    <p class="no-margin">Total</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                {{-- Incorporar nuevas tarjetas --}}

                            </div>
                        </div>






                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')



@if($insumosCriticos->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            title: 'Stock Crítico',
            icon: 'error',
            html: `
                <ul>
                    @foreach ($insumosCriticos as $insumo)
                        <li>{{ $insumo->nombre }}: {{ $insumo->cantidad }} unidades (Stock crítico: {{ $insumo->stock_critico }})</li>
                    @endforeach
                </ul>
            `,
            confirmButtonText: 'Aceptar',
        });
    });
</script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            if (window.Echo) {
                window.Echo.channel('canal-publico')
                    .listen('EjemploEvento', (e) => {
                        console.log(e.mensaje);
    
                        const Toast = Swal.mixin({
                        toast: true,
                        position: "top",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    });
                    
                    Toast.fire({
                        icon: "success",
                        title: e.mensaje
                    });
                    });
            } else {
                console.error("Echo no está definido, verifica la configuración.");
            }
        }, 1000);
    });
    
        </script>
@endsection