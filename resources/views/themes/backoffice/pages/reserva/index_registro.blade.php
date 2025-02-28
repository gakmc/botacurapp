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
    <a href="?page=1"><p class="caption"><strong>Reservas desde {{ now()->format('d-m-Y') }}</strong></p></a>

    <div class="row">
        <div class="col s2 green-text offset-s2">
            <i class='material-icons left'>done_all</i>Registro completo
        </div>
        <div class="col s2 green-text offset-s2">
            <i class='material-icons left'>check</i>Registro incompleto
        </div>
        <div class="col s2 red-text offset-s1">
            <i class='material-icons left'>close</i>No Registra
        </div> 
    </div>
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">


            @if (!isset($reservas))
                <h5 class="center">
                    No se registran reservas
                </h5>
            @else

                    <table class="bordered">
                        <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>WhatsApp</th>
                            <th>Fecha de Reserva</th>
                            <th>Menus</th>
                            <th>Spa</th>
                            <th>Masajes</th>
                        </tr>
                        </thead>
                
                        <tbody>

                        @foreach ($reservas as $reserva)
                        @php
                            $primeraVisita  = $reserva->visitas->first();
                            $ultimaVisita   = $reserva->visitas->last();
                            $visitas        = $reserva->visitas;
                            $menus          = $ultimaVisita->menus;
                            $masajes        = $ultimaVisita->masajes;

                            $totalMenus     = $menus->count();
                            $totalMasajes   = $masajes->count();
                            $totalVisitas   = $visitas->count();

                            $menusConProducto = $menus->filter(function($menu){ 
                                return $menu->id_producto_entrada !== null || $menu->id_producto_fondo !== null;
                            })->count();

                            $masajesConHorario = $masajes->filter(function($masaje){ 
                                return $masaje->horario_masaje !== null;
                            })->count();

                            $visitasConHorario = $visitas->filter(function($visita){
                                return $visita->horario_sauna !== null || $visita->horario_tinaja !== null;
                            })->count();

                            if ($menusConProducto === $totalMenus) {
                                $iconoMenu = 'done_all';
                                $colorMenu = 'green';
                                $linkMenu = '#';
                            } elseif ($menusConProducto > 0) {
                                $iconoMenu = 'check';
                                $colorMenu = 'green';
                                $linkMenu = route('backoffice.reserva.visitas.menu', ['reserva' => $reserva, 'visita' => $reserva->visitas->last()]);
                            } else {
                                $iconoMenu = 'close';
                                $colorMenu = 'red';
                                $linkMenu = route('backoffice.reserva.show', $reserva);
                            }

                            if ($masajesConHorario === $totalMasajes) {
                                $iconoMasaje = 'done_all';
                                $colorMasaje = 'green';
                                $linkMasaje = '#';
                            } elseif ($masajesConHorario > 0) {
                                $iconoMasaje = 'check';
                                $colorMasaje = 'green';
                                $linkMasaje = route('backoffice.reserva.visitas.masaje', ['reserva' => $reserva, 'visita' => $reserva->visitas->last()]);
                            } else {
                                $iconoMasaje = 'close';
                                $colorMasaje = 'red';
                                $linkMasaje = route('backoffice.reserva.show', $reserva);
                            }

                            if ($visitasConHorario === $totalVisitas) {
                                $iconoVisita = 'done_all';
                                $colorVisita = 'green';
                                $linkVisita = '#';
                            } elseif ($visitasConHorario > 0) {
                                $iconoVisita = 'check';
                                $colorVisita = 'green';
                                $linkVisita = route('backoffice.reserva.visitas.spa', ['reserva' => $reserva, 'visita' => $reserva->visitas->first()]);
                            } else {
                                $iconoVisita = 'close';
                                $colorVisita = 'red';
                                $linkVisita = route('backoffice.reserva.show', $reserva);
                            }

                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('backoffice.cliente.show', $reserva->cliente) }}">
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
                                <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                    {{$reserva->fecha_visita}}
                                </a>
                            </td>
                            <td>
                                <a href="{{$linkMenu}}">
                                    <i class='material-icons {{$colorMenu}}-text'>{{$iconoMenu}}</i>
                                </a>
                            </td>
                            <td>
                                <a href="{{$linkVisita}}">
                                    <i class='material-icons {{$colorVisita}}-text'>{{$iconoVisita}}</i>
                                </a>
                            </td>
                            <td>
                                <a href="{{$linkMasaje}}">
                                    <i class='material-icons {{$colorMasaje}}-text'>{{$iconoMasaje}}</i>
                                </a>
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