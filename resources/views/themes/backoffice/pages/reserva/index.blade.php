@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
@endsection

@section('dropdown_settings')
<li><a href="{{route ('backoffice.reservas.listar') }}" class="grey-text text-darken-2">Todas las Reservas</a></li>
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
    <a href="?page=1"><p class="caption"><strong>Reservas desde {{ now()->format('d-m-Y') }}</strong></p></a>
    <div class="row"><div class="col s2 green-text offset-s2"><i class='material-icons left'>fiber_manual_record</i>Pagado</div><div class="col s2 orange-text offset-s1"><i class='material-icons left'>fiber_manual_record</i>Por pagar Consumo</div> <div class="col s2 blue-text offset-s1"><i class='material-icons left'>fiber_manual_record</i>Por Pagar</div></div>
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">
            <a href="{{ route('backoffice.reserva.index', ['alternative' => !$alternativeView]) }}" class="waves-effect waves-light btn right hide-on-small-only hide-on-med-only">
                @if ($alternativeView)
                Horarios <i class='material-icons right'>list</i>
                @else
                Ubicación <i class='material-icons right'>apps</i>
                @endif</a>
                
                <a href="#modalSaunaDisponible" data-target="modal-sauna-disponible" class="waves-effect waves-light btn modal-trigger right hide-on-small-only hide-on-med-only">Horas Disponibles <i class='material-icons right'>access_time</i></a>
            
            {{-- Vista Alternativa --}}
            @if ($alternativeView)

                @php
                    $color = "";
                @endphp

                {{-- Vista Alternativa en Pantallas L --}}
                @foreach($reservasPaginadas as $fecha => $reservas)
                    @include('themes.backoffice.pages.reserva.screens.alternative')
                @endforeach
                {{-- Fin Vista Alternativa en Pantallas L --}}

                
                <!-- Paginación -->
                <div class="center-align">
                    {{ $reservasPaginadas->appends(['alternative' => 1])->links('vendor.pagination.materialize') }}
                </div>

                {{-- Fin Vista Alternativa --}}    

            @else
                {{-- Vista Comun --}}    

                @foreach ($reservasMovilesPaginadas as $fecha => $reservas)

                {{-- Vista en Pantallas de dispositivos Moviles --}}
                    @include('themes.backoffice.pages.reserva.screens.mobile', $reservas)
                {{-- Fin Vista en Pantallas de dispositivos Moviles --}}
                    
                @endforeach


                @foreach($reservasPaginadas as $fecha => $reservas)
                    {{-- Vista en Pantallas L --}}
                    @include('themes.backoffice.pages.reserva.screens.principal', ['reservasPaginadas'=>$reservasPaginadas])
                    {{-- Fin Vista Pantallas L --}}
                @endforeach


                <!-- Paginación -->

                @if ($mobileView === 'masajes')
                                <div class="center-align">
                                    {{ $reservasPaginadas->appends(['mobileview' => 'masajes'])->links('vendor.pagination.materialize') }}
                                </div>
                @elseif ($mobileView === 'ubicacion')
                <div class="center-align">
                    {{ $reservasPaginadas->appends(['mobileview' => 'ubicacion'])->links('vendor.pagination.materialize') }}
                </div>
                @else
                <div class="center-align">
                    {{ $reservasPaginadas->links('vendor.pagination.materialize') }}
                </div>
                    
                @endif

                {{-- Fin Vista Comun --}}
            @endif

                {{-- Modal para mostrar los horarios disponibles --}}
                @include('themes.backoffice.pages.reserva.includes.modal_sauna_disponible')

        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    function activar_alerta(cliente)
    {
        console.log(cliente);
        
        Swal.fire({
            toast: true,
            icon: 'warning',
            title: `${cliente} no registra masajes`,
            color: 'white',
            iconColor: 'white',
            background: "#039B7B",
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
        });
    }
</script>

<script>
    $(document).ready(function() {
        $('.modal').modal();
    });
</script>

{{-- Vista Movil --}}
{{-- <script>


    $(document).ready(function () {
        // Ocultar todas las reservas al inicio excepto las activas
        $(".reserva-card").hide();

        // Al hacer clic en un botón flotante, mostrar las reservas correspondientes
        $(".fixed-action-btn a[data-vista]").on("click", function () {
            let tipo = $(this).data("vista"); // Obtener el tipo de vista

            $(".reserva-card").hide(); // Ocultar todas
            $('.reserva-card[data-tipo="' + tipo + '"]').fadeIn(); // Mostrar solo las que coincidan
        });

        // Al inicio mostrar todas las reservas
        $(".reserva-card").fadeIn();
    });

</script> --}}

@endsection