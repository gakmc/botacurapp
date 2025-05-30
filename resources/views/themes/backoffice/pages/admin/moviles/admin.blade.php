@extends('themes.backoffice.layouts.admin')

@section('title', '')

@section('head')
@endsection


@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear
        Reserva</a></li> --}}
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Menú</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">



                        {{-- CONTENIDO --}}

                        {{-- SOLO visible en móviles --}}
                        <div class="hide-on-med-and-up">

                            {{-- Opción: Ver Sueldos --}}
                            <div class="card hoverable">
                                <div class="card-content center">
                                    <i class="material-icons large cyan-text text-darken-2">attach_money</i>
                                    <h6 class="grey-text text-darken-3">Sueldos</h6>
                                </div>
                                <div class="card-action center">

                                    @if (Auth::user()->has_any_role([config('app.garzon_role') ,
                                    config('app.anfitriona_role') , config('app.barman_role'),
                                    config('app.cocina_role'), config('app.jefe_local_role')]))
                                    <a href="{{ route('backoffice.sueldo.view', Auth::user()) }}"
                                        class="btn-flat cyan-text text-darken-2">
                                        Ver Sueldos
                                    </a>
                                    @endif
                                    @if (Auth::user()->has_any_role([config('app.masoterapeuta_role')]))
                                    <a href="{{ route('backoffice.sueldo.view_maso', Auth::user()) }}"
                                        class="btn-flat cyan-text text-darken-2">
                                        Ver Sueldos
                                    </a>
                                    @endif

                                </div>
                            </div>

                            {{-- Opción: Cerrar Sesión --}}
                            <div class="card hoverable">
                                <div class="card-content center">
                                    <i class="material-icons large red-text text-darken-1">exit_to_app</i>
                                    <h6 class="grey-text text-darken-3">Cerrar Sesión</h6>
                                </div>
                                <div class="card-action center">
                                    <a href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                        class="btn-flat red-text text-darken-1">
                                        Salir
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </div>
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
@endsection