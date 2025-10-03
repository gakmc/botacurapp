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

@if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
    <div class="section">
        <p class="caption"><strong>Administración</strong></p>
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
                                        <a href="{{route('backoffice.reservas.registros')}}">
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

@endif

@if (Auth::user()->has_role(config('app.admin_role')))
    

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

@endif

@if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
    


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
@endif



                                </div>
                            </div>




                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @if (Auth::user()->has_role(config('app.admin_role')))
        

    <div class="section">
        <p class="caption"><strong>Finanzas</strong></p>
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
                                        <a href="{{route('backoffice.admin.ingresos')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-orange-amber gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5">equalizer</i>
                                                        <p>Programas Contratados</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0">{{$totalReservas}}</h5>
                                                        <p class="no-margin">Total</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    {{-- Incorporar nuevas tarjetas --}}

                                    {{-- <div class="col s12 m6 l3">
                                        <a href="{{route('backoffice.admin.consumos')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-indigo-purple gradient-shadow min-height-100 white-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5">shopping_cart</i>
                                                        <p>Consumos y Servicios</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0">{{$totalConsumos}}</h5>
                                                        <p class="no-margin">Total</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div> --}}

                                    <div class="col s12 m6 l3">
                                        <a href="{{route('backoffice.sueldos.index')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-orange-amber gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5">monetization_on</i>
                                                        <p>Remuneraciones <strong>{{ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('MMMM'))}}</strong></p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0">{{number_format($cantidadFuncionarios,0,"",".")}}</h5>
                                                        <p class="no-margin">Funcionarios</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>


                                    <div class="col s12 m6 l3">
                                        <a href="{{route('backoffice.poro-pagado.index')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-orange-amber gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5">redeem</i>
                                                        <p>Poro Poro</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0">{{number_format($poroporo,0,"",".")}}</h5>
                                                        <p class="no-margin">Cantidad Productos</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>


                                    <div class="col s12 m6 l3">
                                        <a href="{{route('backoffice.finanzas.resumen.anual')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-orange-amber gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5">account_balance</i>
                                                        <p>Egresos e Ingresos</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0">{{ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('MMMM'))}}</h5>
                                                        <p class="no-margin"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>


                                    <div class="col s12 m6 l3">
                                        <a href="{{route('backoffice.finanzas.ingresos_percibidos')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-orange-amber gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5">account_balance_wallet</i>
                                                        <p>Ingresos percibidos</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0">{{ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('MMMM'))}}</h5>
                                                        <p class="no-margin"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>



                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="section">
        <p class="caption"><strong>Funcionarios</strong></p>
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
                                        <a href="{{route('backoffice.rango-sueldos.index')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-blue-grey-blue gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5 white-text">equalizer</i>
                                                        <p class="white-text">Sueldos por Roles</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0 white-text">{{$cantidadRoles}}</h5>
                                                        <p class="no-margin white-text">Roles</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>


                                    <div class="col s12 m6 l3">
                                        <a href="{{route('backoffice.usuario-sueldo.index')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-blue-grey-blue gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5 white-text">equalizer</i>
                                                        <p class="white-text">Sueldos por Usuario</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0 white-text">{{$cantidadFuncionarios}}</h5>
                                                        <p class="no-margin white-text">Usuarios</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>


                                    <div class="col s12 m6 l3">
                                        <a href="{{route('backoffice.asistencia.index')}}">
                                            <div class="animate__animated animate__backInLeft card gradient-45deg-blue-grey-blue gradient-shadow min-height-100 black-text"
                                                style="--animate-delay: 1s; --animate-duration: 2s; ">
                                                <div class="padding-4">
                                                    <div class="col s7 m7">
                                                        <i class="material-icons background-round mt-5 white-text">assignment_returned</i>
                                                        <p class="white-text">Asistencia</p>
                                                    </div>
                                                    <div class="col s5 m5 right-align">
                                                        <h5 id="reservas-count" class="mb-0 white-text">{{$asistentesConteo}}</h5>
                                                        <p class="no-margin white-text">Asistentes</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>




                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (Auth::user()->has_role(config('app.anfitriona_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
 
<div class="section">
    <p class="caption"><strong>Caja</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">

                        @php
                            $anio = \Carbon\Carbon::now()->year;
                            $mes = \Carbon\Carbon::now()->month;
                            $dia = \Carbon\Carbon::now()->day;
                        @endphp

                        {{-- CONTENIDO --}}
                        <div id="card-stats">
                            <div class="row mt-1">
                                <!-- Tarjeta para mostrar el número de Reservas -->
                                <div class="col s12 m6 l3">
                                    <a href="{{route('backoffice.admin.cierreCaja',[$anio, $mes, $dia])}}">
                                        <div class="animate__animated animate__backInLeft card gradient-45deg-amber-amber gradient-shadow min-height-100 black-text"
                                            style="--animate-delay: 1s; --animate-duration: 2s; ">
                                            <div class="padding-4">
                                                <div class="col s7 m7">
                                                    <i class="material-icons background-round mt-5">equalizer</i>
                                                    <p>Caja del Dia</p>
                                                </div>
                                                <div class="col s5 m5 right-align">
                                                    <h5 id="reservas-count" class="mb-0">{{$totalAsistentesDia}}</h5>
                                                    <p class="no-margin">Total Asistentes</p>
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

@endif



  
@endsection


@section('foot')


@if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role'))) 
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