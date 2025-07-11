@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
<li><a href="{{route ('backoffice.reservas.registros') }}">Reservas</a></li>
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{route ('backoffice.reservas.registros') }}" class="grey-text text-darken-2">Reservas</a></li> --}}
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
    <a href="?page=1"><p class="caption"><strong>Reservas {{$fechaF}}</strong></p></a>

    <div class="row">
        <div class="col s2 green-text offset-s1">
            <i class='material-icons left'>done_all</i>Registro completo
        </div>
        <div class="col s2 green-text offset-s1">
            <i class='material-icons left'>check</i>Registro incompleto
        </div>
        <div class="col s2 red-text offset-s1">
            <i class='material-icons left'>close</i>No Registra
        </div> 
        <div class="col s2 red-text offset-s1">
            <i class='material-icons left'>do_not_disturb_alt</i>No Aplica
        </div> 
    </div>
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">

            @foreach ($reservasPaginadas as $fecha => $reservas)
            <div id="work-collections">
                <div class="row">
        
                  <div class="col s12 m4 l4">
                    <ul class="collection">
                        <li class="collection-item avatar">
                          <i class="material-icons circle red">group_add</i>
                          <span class="title">Cantidad de Asistentes</span>
                          <p>Total:</p>
                          {{-- <a href="#!" class="secondary-content"><i class="material-icons">group_add</i></a> --}}
                          <span class="secondary-content" style="color: #039B7B">
                            {{$reservas->sum('cantidad_personas')}} {{$reservas->sum('cantidad_personas') > 1 ? "Personas" : "Persona"}}
                          </span>
                        </li>
                      </ul>

                  </div>
                </div>
                                <a href="#modalSaunaDisponible" data-target="modal-sauna-disponible" class="waves-effect waves-light btn modal-trigger right hide-on-small-only hide-on-med-only">Horas Disponibles <i class='material-icons right'>access_time</i></a>
                                <a href="#modalLugaresDisponible" data-target="modal-lugares-disponible" class="waves-effect waves-light btn modal-trigger right hide-on-small-only hide-on-med-only">Lugares Disponibles <i class='material-icons right'>beach_access</i></a>
            </div>

                <p class="caption"><strong>Reservas: {{ $fecha }}</strong></p>
                    @if (!isset($reservas))
                        <h5 class="center">
                            No se registran reservas
                        </h5>
                    @else

                            <table class="bordered centered responsive-table">
                                <thead>
                                <tr>
                                    <th>Recepcionado</th>
                                    <th>Nombre</th>
                                    <th>WhatsApp</th>
                                    <th>Cant. Personas</th>
                                    <th>Programa</th>
                                    <th>Ubicación</th>
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
                                        $primerMasaje = $reserva->masajes->first();
                                        
                                        $menus = $reserva->menus ?? collect();
                                        $masajes = $reserva->masajes ?? collect();
                                        //$masajes = $ultimaVisita->masajes;

                                        $totalMenus = optional($menus)->count();
                                        $totalMasajes = optional($masajes)->count();
                                        $totalVisitas = optional($visitas)->count();

                                        $menusConProducto = $menus->filter(function($menu){ 
                                            return $menu->id_producto_entrada !== null || $menu->id_producto_fondo !== null;
                                        })->count();

                                        $masajesConHorario = $masajes->filter(function($masaje){ 
                                            return $masaje->horario_masaje !== null;
                                        })->count();

                                        $visitasConHorario = $visitas->filter(function($visita){
                                            return $visita->horario_sauna !== null || $visita->horario_tinaja !== null;
                                        })->count();

                                        if ($totalMenus > 0 && ($menusConProducto === $totalMenus)) {
                                            $iconoMenu = 'done_all';
                                            $colorMenu = 'green';
                                            $linkMenu = '#';
                                        } elseif ($menusConProducto > 0) {
                                            $iconoMenu = 'check';
                                            $colorMenu = 'green';
                                            $linkMenu = route('backoffice.reserva.menus', ['reserva' => $reserva]);
                                        } elseif (!$reserva->programa->incluye_almuerzos && !$reserva->incluye_almuerzos_extra) {
                                            $iconoMenu = 'do_not_disturb_alt';
                                            $colorMenu = 'red';
                                            $linkMenu = '#';
                                        } else {
                                            $iconoMenu = 'close';
                                            $colorMenu = 'red';
                                            $linkMenu = ($ultimaVisita) ? route('backoffice.reserva.menus', ['reserva' => $reserva]) : route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);
                                        }

                                        if ($totalMasajes > 0 && ($masajesConHorario === $totalMasajes)) {
                                            $iconoMasaje = 'done_all';
                                            $colorMasaje = 'green';
                                            $linkMasaje = '#';
                                        } elseif ($masajesConHorario > 0) {
                                            $iconoMasaje = 'check';
                                            $colorMasaje = 'green';
                                            $linkMasaje = route('backoffice.reserva.masajes', ['reserva' => $reserva]);
                                        } elseif ( !$reserva->programa->incluye_masajes && !$reserva->incluye_masajes_extra) {
                                            $iconoMasaje = 'do_not_disturb_alt';
                                            $colorMasaje = 'red';
                                            $linkMasaje = '#';
                                        } else {
                                            $iconoMasaje = 'close';
                                            $colorMasaje = 'red';
                                            $linkMasaje = ($ultimaVisita) ? route('backoffice.reserva.masajes', ['reserva' => $reserva]) : route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);
                                        }

                                        if ($totalVisitas > 0 && ($visitasConHorario === $totalVisitas)) {
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
                                            $linkVisita = ($ultimaVisita) ? route('backoffice.reserva.visitas.spa', ['reserva' => $reserva, 'visita' => $reserva->visitas->first()]) : route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]);
                                        }

                                    @endphp
                                    <tr>

                                        <td>
                                            @if (is_null($reserva->estadoRecepcion))
                                                <a id="icono-reserva-{{ $reserva->id }}" onclick="recepcionar({{ $reserva->id }})" class="btn-floating white">
                                                    <i id="icono-reserva-{{ $reserva->id }}" class="material-icons red-text">exit_to_app</i>
                                                </a>
                                            @else
                                                <i class="material-icons green-text tooltipped" style="cursor: pointer" data-position="top" data-tooltip="Recepcionado por {{ $reserva->estadoRecepcion->user->name }}">person_pin</i>
                                            @endif
                                        </td>
                                                                                    

                                        <td>
                                            <a href="{{ route('backoffice.reserva.show', $reserva) }}">
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
                                            {{$reserva->cantidad_personas}}
                                        </td>
                                        <td>

                                            {{$reserva->programa->nombre_programa}}

                                        </td>
                                        <td>
                                            @if(isset($ultimaVisita))
                                                @if (is_null($ultimaVisita->id_ubicacion))
                                                    <a href="{{route('backoffice.visita.edit_ubicacion',['visitum'=>$reserva->visitas->first()])}}">
                                                        No Registrada
                                                    </a>
                                                @else
                                                    {{$ultimaVisita->ubicacion->nombre}}
                                                @endif

                                            @else
                                                <a href="{{ route('backoffice.reserva.visitas.create', ['reserva' => $reserva->id]) }}">
                                                    <p class="red-text"><strong>No se guardo Visita</strong></p>
                                                </a>
                                            @endif
                                            
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
                                            @if (isset($primeraVisita) && $primeraVisita->horario_sauna)
                                                <span>{{$primeraVisita->horario_sauna}}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{$linkMasaje}}">
                                                <i class='material-icons {{$colorMasaje}}-text'>{{$iconoMasaje}}</i>
                                            </a>
                                            @if(isset($primerMasaje) && $primerMasaje->horario_masaje)
                                                <span>{{ $primerMasaje->horario_masaje }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    
                                @endforeach
                                


                                </tbody>
                            </table>


                    @endif


                    @endforeach
                </div>


                    @include('themes.backoffice.pages.reserva.includes.modal_sauna_disponible')
                    @include('themes.backoffice.pages.reserva.includes.modal_lugares_disponible')

    </div>
</div>



<div class="fixed-action-btn toolbar hide-on-large-only" style="bottom: 45px; right: 24px;">
    <a class="btn-floating btn-large blue">
      <i class="material-icons large">apps</i>
    </a>
    <ul>
            <li>
                <a href="#modalLugaresDisponible" data-target="modal-lugares-disponible" class="waves-effect waves-light btn modal-trigger"><i class="material-icons">beach_access</i></a>
            </li>
            <li>
                <a href="#modalSaunaDisponible" data-target="modal-sauna-disponible" class="waves-effect waves-light btn modal-trigger"><i class="material-icons right">access_time</i></a>
            </li>
    </ul>

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

<script>
    $(document).ready(function () {
        $('.modal').modal();
    });
</script>

<script>
    function recepcionar(reservaId) { 
        $.ajax({
            url: '{{route("backoffice.estado_recepcion.store")}}',
            method: 'POST',
            data: {
                reserva_id: reservaId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {

                const icono = document.querySelector(`#icono-reserva-${reservaId}`);
                if (icono) {
                    icono.outerHTML = `
                        <i class="material-icons green-text tooltipped"
                        style="cursor: pointer"
                        data-position="top"
                        data-tooltip="Recepcionado por ${response.user_name}">
                        person_pin
                        </i>`;
                    $('.tooltipped').tooltip(); // reactivar tooltips nuevos
                }

                Swal.fire({
                    toast: true,
                    position: 'center', // Esto lo centra en la pantalla
                    icon: 'success',
                    title: response.message,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'swal2-toast' // importante para conservar estilo toast
                    },
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
            },
            error: function () {
                Swal.fire('Error', 'No se pudo registrar la recepción.', 'error');
            }
        });
        }
</script>

<script>
$(document).ready(function () {
    if (typeof window.Echo !== 'undefined') {
    window.Echo.channel('recepcion-cliente')
    .listen('.cliente-recepcionado', (e) => {
        const icono = document.querySelector(`#icono-reserva-${e.reservaId}`);
        if (icono) {
            icono.outerHTML = `
                <i class="material-icons green-text tooltipped"
                style="cursor: pointer"
                data-position="top"
                data-tooltip="Recepcionado por ${e.userName}">
                person_pin
                </i>`;
            $('.tooltipped').tooltip();
        }
    });
    }else{
        console.error('Echo no está inicializado.');
    }
});
</script>

@endsection