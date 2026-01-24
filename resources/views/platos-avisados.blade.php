@extends('themes.backoffice.layouts.sin-auth')

{{-- @section('title', 'Avisados en Cocina') --}}

@section('content')
<div class="section">
    <p class="caption"><strong>Reservas Avisadas en Cocina</strong></p>
    <div class="center-align">
        <h3 id="hora-actual" class="blue-text text-darken-2"></h3>
    </div>
    <div class="divider"></div>

    <div class="section">
        @foreach($reservas as $fecha => $listaReservas)
        <h5>@if (now()->format('d-m-Y') == $fecha) Hoy @endif {{ $fecha }}</h5>

        <div class="row"> <!-- AÑADIR ESTE WRAPPER -->
@foreach($listaReservas as $reserva)

@if ($reserva->menus->isNotEmpty())

    <div id="menuSelect_{{$reserva->id}}" class="col s12">
        <div class="card-panel ">
            <div class="card-content gradient-45deg-light-blue-cyan">
                <h5 class="card-title center white-text">
                    <i class="material-icons white-text">restaurant_menu</i>
                    Menús para {{ $reserva->cliente->nombre_cliente }} - {{ $reserva->cantidad_personas }} {{($reserva->cantidad_personas >= 1) ? (($reserva->cantidad_personas >= 2) ? "Comensales" : "Comensal") : ""}}

                
                <button id="entregar_{{$reserva->id}}" data-id="{{$reserva->id}}" data-url="{{ route('backoffice.reserva.entregar', $reserva->id) }}" class="btn-floating btn-entregar" onclick="entregado({{$reserva->id}})" @if($reserva->avisado_en_cocina == 'entregado') style="display: none;" @endif>
                    <i class='material-icons'>restaurant</i>
                </button>

                </h5>
            </div>

            <div class="card-content grey lighten-4">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>Menú</th>
                            <th>Entrada</th>
                            <th>Fondo</th>
                            <th>Acompañamiento</th>
                            <th>Alérgias</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reserva->menus as $index => $menu)
                        <tr>
                            <td><strong>Menú {{ $index + 1 }}:</strong></td>
                            <td>{{ $menu->productoEntrada->nombre ?? 'No registra' }}</td>
                            <td>{{ $menu->productoFondo->nombre ?? 'No registra' }}</td>
                            <td>{{ $menu->productoAcompanamiento->nombre ?? 'Sin Acompañamiento' }}</td>
                            <td class="{{ $menu->alergias ? 'red-text' : '' }}">{{ $menu->alergias ?? 'No Registra' }}</td>
                            <td class="{{ $menu->observacion ? 'red-text' : '' }}">{{ $menu->observacion ?? 'No Registra' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @endif
    
    @endforeach
</div> <!-- CERRAR EL WRAPPER -->

        @endforeach
    </div>
</div>
@endsection

@section('foot')

<script>
    function entregado(id) { 
        const entregado = $('#entregar_'+id);
        const url = entregado.data('url');
        const menu = $('#menuSelect_'+id);

        
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                _method: 'PUT',
                id: id
            },
            success: function (response) {
            
                const Toast = Swal.mixin({
                    toast:false,
                    showConfirmButton: true,
                    title: "Se entrego el menú de "+response.nombreCliente,
                    icon: "success",
                    showClass: {
                        popup: `
                        animate__animated
                        animate__fadeInUp
                        animate__faster
                        `
                    },
                    hideClass: {
                        popup: `
                        animate__animated
                        animate__fadeOutDown
                        animate__faster
                        `
                    },
                });

                
                Toast.fire();
                menu.hide();
            },
            error: function(){
                Swal.fire('Error', 'No fue posible registrar la entrega.','error');
            }
            
        });
        
     }
</script>

<script>
$(document).ready(function () {
    if (typeof window.Echo !== 'undefined') {
        // Canal para avisos nuevos desde cocina
        window.Echo.channel('aviso-cocina')
            .listen('.reservaAvisada', (e) => {
                // Reproducir sonido
                const audio = new Audio('/sounds/notificacionv2.mp3');
                audio.play();

                audio.onended = () => {
                    location.reload();
                };
            });



        window.Echo.channel('entregar-menu')
            .listen('.menuEntregado', (e) => {
                const entregado = $(`#entregar_${e.idReserva}`);
                const menu = $(`#menuSelect_${e.idReserva}`);

                menu.hide();

            });

    }else{
        console.error('Echo no está inicializado.');
    }
  });
</script>

<script>
  function actualizarHora() {
    const ahora = new Date();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    const segundos = String(ahora.getSeconds()).padStart(2, '0');
    const horaFormateada = `${horas}:${minutos}:${segundos}`;
    $('#hora-actual').text(horaFormateada);
  }

  $(document).ready(function () {
    actualizarHora(); // actualizar al cargar
    setInterval(actualizarHora, 1000); // actualizar cada segundo
  });
</script>
@endsection